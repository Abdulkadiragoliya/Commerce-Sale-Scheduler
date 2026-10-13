<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesalescheduler\records\SaleRecord;
use abdulkadiragoliya\commercesalescheduler\records\SaleProductRecord;
use abdulkadiragoliya\commercesalescheduler\records\SaleVariantRecord;
use abdulkadiragoliya\commercesalescheduler\records\SaleCategoryRecord;
use abdulkadiragoliya\commercesalescheduler\records\SaleExclusionRecord;
use abdulkadiragoliya\commercesalescheduler\records\SaleSiteRecord;
use DateTime;
use DateTimeInterface;

/**
 * Primary Sales Service
 */
class Sales extends Component
{
    /**
     * Get a sale by its ID
     */
    public function getSaleById(int $id): ?Sale
    {
        $record = SaleRecord::findOne($id);
        if (!$record) {
            return null;
        }

        return $this->createSaleFromRecord($record);
    }

    /**
     * Get a sale by its UID
     */
    public function getSaleByUid(string $uid): ?Sale
    {
        $record = SaleRecord::findOne(['uid' => $uid]);
        if (!$record) {
            return null;
        }

        return $this->createSaleFromRecord($record);
    }

    /**
     * Get all sales matching optional search/filter criteria
     */
    public function getAllSales(array $criteria = []): array
    {
        $query = SaleRecord::find();

        if (!empty($criteria['search'])) {
            $query->andWhere(['or',
                ['like', 'name', $criteria['search']],
                ['like', 'description', $criteria['search']],
            ]);
        }

        if (isset($criteria['status']) && $criteria['status'] !== 'all' && $criteria['status'] !== '') {
            $nowDb = Db::prepareDateForDb(new DateTime('now'));
            switch ($criteria['status']) {
                case Sale::STATUS_ACTIVE:
                    $query->andWhere(['enabled' => true])
                          ->andWhere(['or', ['dateFrom' => null], ['<=', 'dateFrom', $nowDb]])
                          ->andWhere(['or', ['dateTo' => null], ['>=', 'dateTo', $nowDb]]);
                    break;
                case Sale::STATUS_SCHEDULED:
                    $query->andWhere(['enabled' => true])
                          ->andWhere(['>', 'dateFrom', $nowDb]);
                    break;
                case Sale::STATUS_EXPIRED:
                    $query->andWhere(['<', 'dateTo', $nowDb]);
                    break;
                case Sale::STATUS_DISABLED:
                    $query->andWhere(['enabled' => false])
                          ->andWhere(['or', ['dateTo' => null], ['>=', 'dateTo', $nowDb]]);
                    break;
            }
        }

        $orderBy = $criteria['orderBy'] ?? 'priority DESC, id DESC';
        $query->orderBy($orderBy);

        if (!empty($criteria['limit'])) {
            $query->limit((int)$criteria['limit']);
        }
        if (!empty($criteria['offset'])) {
            $query->offset((int)$criteria['offset']);
        }

        $records = $query->all();
        $sales = [];

        foreach ($records as $record) {
            $sales[] = $this->createSaleFromRecord($record);
        }

        return $sales;
    }

    /**
     * Get all currently active sales
     *
     * @return Sale[]
     */
    public function getActiveSales(?DateTimeInterface $now = null, ?int $siteId = null): array
    {
        $now = $now ?? new DateTime('now');
        $nowDb = Db::prepareDateForDb($now);

        $query = SaleRecord::find()
            ->where(['enabled' => true])
            ->andWhere(['or', ['dateFrom' => null], ['<=', 'dateFrom', $nowDb]])
            ->andWhere(['or', ['dateTo' => null], ['>=', 'dateTo', $nowDb]])
            ->orderBy('priority DESC, id DESC');

        $records = $query->all();
        $sales = [];

        foreach ($records as $record) {
            $sale = $this->createSaleFromRecord($record);
            if ($siteId && !$sale->allSites && !empty($sale->siteIds) && !in_array($siteId, $sale->siteIds, true)) {
                continue;
            }
            $sales[] = $sale;
        }

        return $sales;
    }

    /**
     * Save a sale model and its relations
     */
    public function saveSale(Sale $sale, bool $runValidation = true): bool
    {
        $isNew = !$sale->id;

        if ($runValidation && !$sale->validate()) {
            return false;
        }

        if ($isNew) {
            $record = new SaleRecord();
        } else {
            $record = SaleRecord::findOne($sale->id);
            if (!$record) {
                $sale->addError('id', Craft::t('commerce-sale-scheduler', 'No sale exists with ID {id}', ['id' => $sale->id]));
                return false;
            }
        }

        $record->name = $sale->name;
        $record->description = $sale->description;
        $record->enabled = $sale->enabled;
        $record->priority = $sale->priority;
        $record->discountType = $sale->discountType;
        $record->discountValue = $sale->discountValue;
        $record->dateFrom = $sale->dateFrom ? Db::prepareDateForDb($sale->dateFrom) : null;
        $record->dateTo = $sale->dateTo ? Db::prepareDateForDb($sale->dateTo) : null;
        $record->applyToAllVariants = $sale->applyToAllVariants;
        $record->allSites = $sale->allSites;
        $record->tagAssetId = $sale->tagAssetId ?: null;
        $record->tagText = $sale->tagText;
        $record->tagEnabled = $sale->tagEnabled;
        $record->tagPositionListing = $sale->tagPositionListing;
        $record->tagPositionDetail = $sale->tagPositionDetail;
        $record->tagWidth = $sale->tagWidth;
        $record->tagCustomClass = $sale->tagCustomClass;
        $record->countdownEnabled = $sale->countdownEnabled;
        $record->countdownStyle = $sale->countdownStyle;
        $record->countdownHeading = $sale->countdownHeading;
        $record->countdownShowListing = $sale->countdownShowListing;
        $record->countdownShowDetail = $sale->countdownShowDetail;
        $record->countdownHeadingColor = $sale->countdownHeadingColor;
        $record->countdownDigitsColor = $sale->countdownDigitsColor;
        $record->countdownCustomClass = $sale->countdownCustomClass;
        $record->priceShowOriginal = $sale->priceShowOriginal;
        $record->priceShowSavings = $sale->priceShowSavings;
        $record->priceShowBadge = $sale->priceShowBadge;
        $record->badgeText = $sale->badgeText;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$record->save(false)) {
                $transaction->rollBack();
                return false;
            }

            $sale->id = (int)$record->id;
            $sale->uid = $record->uid;

            $this->saveRelations($sale);

            $transaction->commit();
            Plugin::getInstance()->cache->invalidateAll();

            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Craft::error('Error saving sale: ' . $e->getMessage(), __METHOD__);
            $sale->addError('general', $e->getMessage());
            return false;
        }
    }

    /**
     * Duplicate a sale
     */
    public function duplicateSale(int $id): ?Sale
    {
        $original = $this->getSaleById($id);
        if (!$original) {
            return null;
        }

        $copy = new Sale();
        $copy->setAttributes($original->getAttributes(), false);
        $copy->id = null;
        $copy->uid = null;
        $copy->name = $original->name . ' (Copy)';
        $copy->enabled = false; // Duplicates start disabled for safety
        $copy->productIds = $original->productIds;
        $copy->variantIds = $original->variantIds;
        $copy->categoryIds = $original->categoryIds;
        $copy->exclusionIds = $original->exclusionIds;
        $copy->siteIds = $original->siteIds;

        if ($this->saveSale($copy)) {
            return $copy;
        }

        return null;
    }

    /**
     * Delete a sale
     */
    public function deleteSale(Sale $sale): bool
    {
        if (!$sale->id) {
            return false;
        }

        return $this->deleteSaleById($sale->id);
    }

    /**
     * Delete a sale by ID
     */
    public function deleteSaleById(int $id): bool
    {
        $record = SaleRecord::findOne($id);
        if (!$record) {
            return false;
        }

        $record->delete();
        Plugin::getInstance()->cache->invalidateAll();

        return true;
    }

    /**
     * Toggle enabled status
     */
    public function toggleStatus(int $id): bool
    {
        $sale = $this->getSaleById($id);
        if (!$sale) {
            return false;
        }

        $sale->enabled = !$sale->enabled;
        return $this->saveSale($sale, false);
    }

    /**
     * Execute bulk action (enable, disable, delete)
     */
    public function bulkAction(string $action, array $ids): int
    {
        $affected = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($action === 'delete') {
                if ($this->deleteSaleById($id)) {
                    $affected++;
                }
            } elseif ($action === 'duplicate') {
                if ($this->duplicateSale($id)) {
                    $affected++;
                }
            } elseif ($action === 'enable') {
                $sale = $this->getSaleById($id);
                if ($sale) {
                    $sale->enabled = true;
                    if ($this->saveSale($sale, false)) {
                        $affected++;
                    }
                }
            } elseif ($action === 'disable') {
                $sale = $this->getSaleById($id);
                if ($sale) {
                    $sale->enabled = false;
                    if ($this->saveSale($sale, false)) {
                        $affected++;
                    }
                }
            }
        }

        return $affected;
    }

    /**
     * Dashboard statistics calculation
     */
    public function getDashboardStats(): array
    {
        $all = $this->getAllSales();
        $now = new DateTime('now');
        $expiringThreshold = (new DateTime('now'))->modify('+48 hours');

        $stats = [
            'total' => count($all),
            'active' => 0,
            'scheduled' => 0,
            'expired' => 0,
            'disabled' => 0,
            'conflicts' => 0,
            'expiringSoon' => 0,
        ];

        foreach ($all as $sale) {
            $status = $sale->getStatus($now);
            if (isset($stats[$status])) {
                $stats[$status]++;
            }

            if ($sale->isActive($now) && $sale->dateTo && $sale->dateTo <= $expiringThreshold) {
                $stats['expiringSoon']++;
            }
        }

        $conflicts = $this->detectConflicts($all);
        $stats['conflicts'] = count($conflicts);

        return $stats;
    }

    /**
     * Detect pricing conflicts among sales
     */
    public function detectConflicts(array $sales = []): array
    {
        if (empty($sales)) {
            $sales = $this->getAllSales();
        }

        $activeSales = array_filter($sales, fn(Sale $s) => $s->enabled && !$s->isExpired());
        $conflicts = [];

        $salesList = array_values($activeSales);
        $count = count($salesList);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $s1 = $salesList[$i];
                $s2 = $salesList[$j];

                // Check time overlap
                if (!$this->timeOverlaps($s1, $s2)) {
                    continue;
                }

                // Check product overlaps
                $sharedProducts = array_intersect($s1->productIds, $s2->productIds);
                $sharedCategories = array_intersect($s1->categoryIds, $s2->categoryIds);

                if (!empty($sharedProducts) || !empty($sharedCategories)) {
                    $conflicts[] = [
                        'sale1' => $s1,
                        'sale2' => $s2,
                        'reason' => !empty($sharedProducts) 
                            ? Craft::t('commerce-sale-scheduler', 'Sales share {count} direct product(s)', ['count' => count($sharedProducts)])
                            : Craft::t('commerce-sale-scheduler', 'Sales share {count} category assignment(s)', ['count' => count($sharedCategories)]),
                    ];
                }
            }
        }

        return $conflicts;
    }

    protected function timeOverlaps(Sale $s1, Sale $s2): bool
    {
        $start1 = $s1->dateFrom ? $s1->dateFrom->getTimestamp() : 0;
        $end1 = $s1->dateTo ? $s1->dateTo->getTimestamp() : PHP_INT_MAX;

        $start2 = $s2->dateFrom ? $s2->dateFrom->getTimestamp() : 0;
        $end2 = $s2->dateTo ? $s2->dateTo->getTimestamp() : PHP_INT_MAX;

        return ($start1 <= $end2) && ($end1 >= $start2);
    }

    protected function createSaleFromRecord(SaleRecord $record): Sale
    {
        $sale = new Sale();
        $sale->id = (int)$record->id;
        $sale->name = (string)$record->name;
        $sale->description = $record->description;
        $sale->enabled = (bool)$record->enabled;
        $sale->priority = (int)$record->priority;
        $sale->discountType = (string)$record->discountType;
        $sale->discountValue = (float)$record->discountValue;
        $sale->dateFrom = $record->dateFrom ? DateTimeHelper::toDateTime($record->dateFrom) : null;
        $sale->dateTo = $record->dateTo ? DateTimeHelper::toDateTime($record->dateTo) : null;
        $sale->applyToAllVariants = (bool)$record->applyToAllVariants;
        $sale->allSites = (bool)$record->allSites;
        $sale->tagAssetId = $record->tagAssetId ? (int)$record->tagAssetId : null;
        $sale->tagText = $record->tagText;
        $sale->tagEnabled = (bool)$record->tagEnabled;
        $sale->tagPositionListing = (string)$record->tagPositionListing;
        $sale->tagPositionDetail = (string)$record->tagPositionDetail;
        $sale->tagWidth = (string)($record->tagWidth ?? '80px');
        $sale->tagCustomClass = $record->tagCustomClass;
        $sale->countdownEnabled = (bool)$record->countdownEnabled;
        $sale->countdownStyle = (string)$record->countdownStyle;
        $sale->countdownHeading = $record->countdownHeading;
        $sale->countdownShowListing = (bool)$record->countdownShowListing;
        $sale->countdownShowDetail = (bool)$record->countdownShowDetail;
        $sale->countdownHeadingColor = $record->countdownHeadingColor;
        $sale->countdownDigitsColor = $record->countdownDigitsColor;
        $sale->countdownCustomClass = $record->countdownCustomClass;
        $sale->priceShowOriginal = (bool)$record->priceShowOriginal;
        $sale->priceShowSavings = (bool)$record->priceShowSavings;
        $sale->priceShowBadge = (bool)$record->priceShowBadge;
        $sale->badgeText = $record->badgeText;
        $sale->uid = $record->uid;
        $sale->dateCreated = $record->dateCreated ? DateTimeHelper::toDateTime($record->dateCreated) : null;
        $sale->dateUpdated = $record->dateUpdated ? DateTimeHelper::toDateTime($record->dateUpdated) : null;

        $this->populateRelations($sale);

        return $sale;
    }

    protected function populateRelations(Sale $sale): void
    {
        if (!$sale->id) {
            return;
        }

        $sale->productIds = (new Query())
            ->select(['productId'])
            ->from(SaleProductRecord::TABLE)
            ->where(['saleId' => $sale->id])
            ->column();
        $sale->productIds = array_map('intval', $sale->productIds);

        $sale->variantIds = (new Query())
            ->select(['variantId'])
            ->from(SaleVariantRecord::TABLE)
            ->where(['saleId' => $sale->id])
            ->column();
        $sale->variantIds = array_map('intval', $sale->variantIds);

        $sale->categoryIds = (new Query())
            ->select(['categoryId'])
            ->from(SaleCategoryRecord::TABLE)
            ->where(['saleId' => $sale->id])
            ->column();
        $sale->categoryIds = array_map('intval', $sale->categoryIds);

        $sale->exclusionIds = (new Query())
            ->select(['productId'])
            ->from(SaleExclusionRecord::TABLE)
            ->where(['saleId' => $sale->id])
            ->column();
        $sale->exclusionIds = array_map('intval', $sale->exclusionIds);

        $sale->siteIds = (new Query())
            ->select(['siteId'])
            ->from(SaleSiteRecord::TABLE)
            ->where(['saleId' => $sale->id])
            ->column();
        $sale->siteIds = array_map('intval', $sale->siteIds);
    }

    protected function saveRelations(Sale $sale): void
    {
        $db = Craft::$app->getDb();
        $now = Db::prepareDateForDb(new DateTime('now'));

        // Products
        $db->createCommand()->delete(SaleProductRecord::TABLE, ['saleId' => $sale->id])->execute();
        if (!empty($sale->productIds)) {
            $rows = [];
            foreach (array_unique($sale->productIds) as $pid) {
                if ($pid) {
                    $rows[] = [$sale->id, (int)$pid, $now];
                }
            }
            if (!empty($rows)) {
                $db->createCommand()->batchInsert(SaleProductRecord::TABLE, ['saleId', 'productId', 'dateCreated'], $rows)->execute();
            }
        }

        // Variants
        $db->createCommand()->delete(SaleVariantRecord::TABLE, ['saleId' => $sale->id])->execute();
        if (!empty($sale->variantIds)) {
            $rows = [];
            foreach (array_unique($sale->variantIds) as $vid) {
                if ($vid) {
                    $rows[] = [$sale->id, (int)$vid, $now];
                }
            }
            if (!empty($rows)) {
                $db->createCommand()->batchInsert(SaleVariantRecord::TABLE, ['saleId', 'variantId', 'dateCreated'], $rows)->execute();
            }
        }

        // Categories
        $db->createCommand()->delete(SaleCategoryRecord::TABLE, ['saleId' => $sale->id])->execute();
        if (!empty($sale->categoryIds)) {
            $rows = [];
            foreach (array_unique($sale->categoryIds) as $cid) {
                if ($cid) {
                    $rows[] = [$sale->id, (int)$cid, $now];
                }
            }
            if (!empty($rows)) {
                $db->createCommand()->batchInsert(SaleCategoryRecord::TABLE, ['saleId', 'categoryId', 'dateCreated'], $rows)->execute();
            }
        }

        // Exclusions
        $db->createCommand()->delete(SaleExclusionRecord::TABLE, ['saleId' => $sale->id])->execute();
        if (!empty($sale->exclusionIds)) {
            $rows = [];
            foreach (array_unique($sale->exclusionIds) as $eid) {
                if ($eid) {
                    $rows[] = [$sale->id, (int)$eid, $now];
                }
            }
            if (!empty($rows)) {
                $db->createCommand()->batchInsert(SaleExclusionRecord::TABLE, ['saleId', 'productId', 'dateCreated'], $rows)->execute();
            }
        }

        // Sites
        $db->createCommand()->delete(SaleSiteRecord::TABLE, ['saleId' => $sale->id])->execute();
        if (!$sale->allSites && !empty($sale->siteIds)) {
            $rows = [];
            foreach (array_unique($sale->siteIds) as $sid) {
                if ($sid) {
                    $rows[] = [$sale->id, (int)$sid, $now];
                }
            }
            if (!empty($rows)) {
                $db->createCommand()->batchInsert(SaleSiteRecord::TABLE, ['saleId', 'siteId', 'dateCreated'], $rows)->execute();
            }
        }
    }
}
