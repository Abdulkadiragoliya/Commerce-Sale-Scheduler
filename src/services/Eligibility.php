<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\db\Query;
use craft\elements\Category;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use DateTime;
use DateTimeInterface;

/**
 * Sale Eligibility & Precedence Service
 */
class Eligibility extends Component
{
    public const RANK_VARIANT = 3;
    public const RANK_PRODUCT = 2;
    public const RANK_CATEGORY = 1;

    /**
     * Resolves the single winning sale for a product or variant
     */
    public function getWinningSale($element, ?DateTimeInterface $now = null, ?int $siteId = null): ?Sale
    {
        $siteId = $siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        $now = $now ?? new DateTime('now');

        $activeSales = Plugin::getInstance()->sales->getActiveSales($now, $siteId);
        if (empty($activeSales)) {
            return null;
        }

        $candidates = [];

        foreach ($activeSales as $sale) {
            $rank = $this->evaluateMatch($sale, $element, $now, $siteId);
            if ($rank !== null) {
                $candidates[] = [
                    'sale' => $sale,
                    'rank' => $rank,
                    'priority' => $sale->priority,
                    'dateTo' => $sale->dateTo ? $sale->dateTo->getTimestamp() : PHP_INT_MAX,
                    'id' => $sale->id ?? 0,
                ];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Sort by Specificity Rank DESC, Priority DESC, DateTo ASC, ID DESC
        usort($candidates, function ($a, $b) {
            if ($a['rank'] !== $b['rank']) {
                return $b['rank'] <=> $a['rank'];
            }
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] <=> $a['priority'];
            }
            if ($a['dateTo'] !== $b['dateTo']) {
                return $a['dateTo'] <=> $b['dateTo'];
            }
            return $b['id'] <=> $a['id'];
        });

        return $candidates[0]['sale'];
    }

    /**
     * Evaluates whether a sale matches an element.
     * Returns specificity rank (3 = variant, 2 = product, 1 = category) or null if no match.
     */
    public function evaluateMatch(Sale $sale, $element, ?DateTimeInterface $now = null, ?int $siteId = null): ?int
    {
        if (!$sale->isActive($now)) {
            return null;
        }

        $siteId = $siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        if (!$sale->allSites && !empty($sale->siteIds) && !in_array($siteId, $sale->siteIds, true)) {
            return null;
        }

        $productId = null;
        $variantId = null;
        $product = null;

        if ($element instanceof Variant) {
            $variantId = (int)$element->id;
            $productId = (int)$element->productId;
            $product = $element->getProduct();
        } elseif ($element instanceof Product) {
            $productId = (int)$element->id;
            $product = $element;
            $variantId = $element->defaultVariant ? (int)$element->defaultVariant->id : null;
        } elseif (is_numeric($element)) {
            $productId = (int)$element;
        } else {
            return null;
        }

        // 1. Exclusions check - highest precedence
        if ($productId && !empty($sale->exclusionIds) && in_array($productId, $sale->exclusionIds, true)) {
            return null;
        }

        // 2. Direct variant check (Rank 3)
        if ($variantId && !empty($sale->variantIds) && in_array($variantId, $sale->variantIds, true)) {
            return self::RANK_VARIANT;
        }

        // 3. Direct product check (Rank 2)
        if ($productId && !empty($sale->productIds) && in_array($productId, $sale->productIds, true)) {
            if ($sale->applyToAllVariants) {
                return self::RANK_PRODUCT;
            }
            // If specific variants required but this variant wasn't in variantIds
            if ($variantId && !empty($sale->variantIds) && !in_array($variantId, $sale->variantIds, true)) {
                return null;
            }
            return self::RANK_PRODUCT;
        }

        // 4. Category match (Rank 1)
        if (!empty($sale->categoryIds) && $productId) {
            if ($this->isProductInCategories($productId, $sale->categoryIds)) {
                if (!$sale->applyToAllVariants && $variantId && !empty($sale->variantIds) && !in_array($variantId, $sale->variantIds, true)) {
                    return null;
                }
                return self::RANK_CATEGORY;
            }
        }

        return null;
    }

    /**
     * Checks if a product belongs to any of the given category IDs
     */
    public function isProductInCategories(int $productId, array $categoryIds): bool
    {
        if (empty($categoryIds)) {
            return false;
        }

        // Check standard Craft relations table
        $hasRelation = (new Query())
            ->from('{{%relations}}')
            ->where([
                'sourceId' => $productId,
                'targetId' => $categoryIds,
            ])
            ->orWhere([
                'sourceId' => $categoryIds,
                'targetId' => $productId,
            ])
            ->exists();

        if ($hasRelation) {
            return true;
        }

        // Check via Category Element Query if element exists
        try {
            $related = Category::find()
                ->id($categoryIds)
                ->relatedTo($productId)
                ->exists();

            if ($related) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    /**
     * Get affected product and variant counts for a sale
     */
    public function getAffectedCounts(Sale $sale): array
    {
        $productCount = 0;
        $variantCount = 0;

        $productIds = $sale->productIds;

        // Add category products
        if (!empty($sale->categoryIds)) {
            $catProductIds = (new Query())
                ->select(['sourceId'])
                ->from('{{%relations}}')
                ->where(['targetId' => $sale->categoryIds])
                ->column();

            $catProductIdsRev = (new Query())
                ->select(['targetId'])
                ->from('{{%relations}}')
                ->where(['sourceId' => $sale->categoryIds])
                ->column();

            $allCatProducts = array_unique(array_merge($catProductIds, $catProductIdsRev));
            $productIds = array_unique(array_merge($productIds, $allCatProducts));
        }

        // Filter exclusions
        if (!empty($sale->exclusionIds)) {
            $productIds = array_diff($productIds, $sale->exclusionIds);
        }

        $productCount = count($productIds);

        if ($sale->applyToAllVariants) {
            if (!empty($productIds)) {
                $variantCount = (int)(new Query())
                    ->from('{{%commerce_variants}}')
                    ->where(['productId' => $productIds])
                    ->count();
            }
        } else {
            $variantCount = count($sale->variantIds);
        }

        return [
            'products' => $productCount,
            'variants' => $variantCount,
        ];
    }
}
