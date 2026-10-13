<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\web\twig;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\Html;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Twig Extension providing craft.saleScheduler functions
 */
class Extension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'saleScheduler' => $this,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('saleSchedulerTag', [$this, 'tag'], ['is_safe' => ['html']]),
            new TwigFunction('saleSchedulerPrice', [$this, 'price'], ['is_safe' => ['html']]),
            new TwigFunction('saleSchedulerCountdown', [$this, 'countdown'], ['is_safe' => ['html']]),
            new TwigFunction('saleSchedulerBlock', [$this, 'saleBlock'], ['is_safe' => ['html']]),
            new TwigFunction('saleSchedulerCss', [$this, 'css'], ['is_safe' => ['html']]),
            new TwigFunction('saleSchedulerJs', [$this, 'js'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render promotional tag
     */
    public function tag($element, array $options = []): Markup
    {
        $html = Plugin::getInstance()->tagManager->renderTag($element, $options);
        return new Markup($html, 'UTF-8');
    }

    /**
     * Render countdown timer
     */
    public function countdown($element, array $options = []): Markup
    {
        $html = Plugin::getInstance()->countdown->renderCountdown($element, $options);
        return new Markup($html, 'UTF-8');
    }

    /**
     * Render price component with strikethrough original and sale price
     */
    public function price($element, array $options = []): Markup
    {
        $purchasable = $this->resolvePurchasable($element);
        if (!$purchasable) {
            return new Markup('', 'UTF-8');
        }

        $originalPrice = (float)$purchasable->getPrice();
        $currency = Craft::$app->has('commerce') 
            ? \craft\commerce\Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso() 
            : 'USD';

        $sale = Plugin::getInstance()->eligibility->getWinningSale($purchasable);
        $calc = $sale ? Plugin::getInstance()->discounts->calculate($originalPrice, $sale, $currency) : null;

        $showOriginal = isset($options['showOriginal']) ? (bool)$options['showOriginal'] : ($sale ? (bool)$sale->priceShowOriginal : true);
        $showSavings = isset($options['showSavings']) ? (bool)$options['showSavings'] : ($sale ? (bool)$sale->priceShowSavings : true);
        $showBadge = isset($options['showBadge']) ? (bool)$options['showBadge'] : ($sale ? (bool)$sale->priceShowBadge : true);
        $customClass = Html::encode($options['class'] ?? '');

        if (!$calc || !$calc->isSaleApplied) {
            // No sale applied - render regular price
            $formattedRegular = $this->formatCurrency($originalPrice, $currency);
            $html = <<<HTML
<div class="sale-price-display {$customClass}">
    <span class="sale-price-display__current">{$formattedRegular}</span>
</div>
HTML;
            return new Markup($html, 'UTF-8');
        }

        $formattedSale = $calc->getFormattedSalePrice();
        $formattedOrig = $calc->getFormattedOriginalPrice();
        $formattedSavings = $calc->getFormattedSavingsAmount();
        $formattedPercent = $calc->getFormattedSavingsPercent();

        $origHtml = $showOriginal 
            ? "<span class=\"sale-price-display__original\"><del>{$formattedOrig}</del></span>" 
            : '';

        $badgeHtml = '';
        if ($showBadge) {
            if (!empty($sale->badgeText)) {
                $customBadge = Html::encode($sale->badgeText);
                $badgeHtml = "<span class=\"sale-price-display__badge\">{$customBadge}</span>";
            } elseif (!$showSavings) {
                // If savings pill is off, display the discount badge
                $badgeHtml = "<span class=\"sale-price-display__badge\">Save {$formattedPercent}</span>";
            }
        }

        $savingsHtml = '';
        if ($showSavings) {
            $savingsHtml = "<span class=\"sale-price-display__savings\">Save {$formattedSavings} ({$formattedPercent})</span>";
        }

        $html = <<<HTML
<div class="sale-price-display sale-price-display--on-sale {$customClass}" data-sale-id="{$sale->id}">
    <span class="sale-price-display__current">{$formattedSale}</span>
    {$origHtml}
    {$badgeHtml}
    {$savingsHtml}
</div>
HTML;

        return new Markup($html, 'UTF-8');
    }

    /**
     * Render full cohesive sale block (Tag + Price + Countdown)
     */
    public function saleBlock($element, array $options = []): Markup
    {
        $sale = Plugin::getInstance()->eligibility->getWinningSale($element);
        if (!$sale) {
            return new Markup('', 'UTF-8');
        }

        $tagHtml = $this->tag($element, $options);
        $priceHtml = $this->price($element, $options);
        $countdownHtml = $this->countdown($element, $options);
        $customClass = Html::encode($options['class'] ?? '');

        $html = <<<HTML
<div class="sale-scheduler-block {$customClass}" data-sale-id="{$sale->id}">
    {$tagHtml}
    <div class="sale-scheduler-block__content">
        {$priceHtml}
        {$countdownHtml}
    </div>
</div>
HTML;

        return new Markup($html, 'UTF-8');
    }

    /**
     * Get winning sale model for an element
     */
    public function getSale($element): ?Sale
    {
        return Plugin::getInstance()->eligibility->getWinningSale($element);
    }

    /**
     * Get comprehensive sale and pricing info array
     */
    public function getSaleInfo($element): array
    {
        $purchasable = $this->resolvePurchasable($element);
        if (!$purchasable) {
            return ['hasSale' => false];
        }

        $originalPrice = (float)$purchasable->getPrice();
        $currency = Craft::$app->has('commerce') 
            ? \craft\commerce\Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso() 
            : 'USD';

        $sale = Plugin::getInstance()->eligibility->getWinningSale($purchasable);
        if (!$sale) {
            return [
                'hasSale' => false,
                'sale' => null,
                'originalPrice' => $originalPrice,
                'salePrice' => $originalPrice,
                'savingsAmount' => 0.0,
                'savingsPercent' => 0.0,
                'formattedOriginalPrice' => $this->formatCurrency($originalPrice, $currency),
                'formattedSalePrice' => $this->formatCurrency($originalPrice, $currency),
                'currency' => $currency,
            ];
        }

        $calc = Plugin::getInstance()->discounts->calculate($originalPrice, $sale, $currency);

        return [
            'hasSale' => $calc->isSaleApplied,
            'sale' => $sale,
            'originalPrice' => $calc->originalPrice,
            'salePrice' => $calc->salePrice,
            'savingsAmount' => $calc->savingsAmount,
            'savingsPercent' => $calc->savingsPercent,
            'formattedOriginalPrice' => $calc->getFormattedOriginalPrice(),
            'formattedSalePrice' => $calc->getFormattedSalePrice(),
            'formattedSavingsAmount' => $calc->getFormattedSavingsAmount(),
            'formattedSavingsPercent' => $calc->getFormattedSavingsPercent(),
            'currency' => $currency,
        ];
    }

    /**
     * Output CSS stylesheet tag or link
     */
    public function css(): Markup
    {
        try {
            $url = Craft::$app->getAssetManager()->getPublishedUrl(
                dirname(__DIR__, 2) . '/web/assets/storefront/salescheduler.css',
                true
            );
            if ($url) {
                return new Markup("<link rel=\"stylesheet\" href=\"{$url}\">", 'UTF-8');
            }
        } catch (\Throwable $e) {
        }

        // Inline fallback
        $cssPath = dirname(__DIR__, 2) . '/web/assets/storefront/salescheduler.css';
        if (file_exists($cssPath)) {
            $css = file_get_contents($cssPath);
            return new Markup("<style>{$css}</style>", 'UTF-8');
        }

        return new Markup('', 'UTF-8');
    }

    /**
     * Output JS script tag
     */
    public function js(): Markup
    {
        try {
            $url = Craft::$app->getAssetManager()->getPublishedUrl(
                dirname(__DIR__, 2) . '/web/assets/storefront/salescheduler.js',
                true
            );
            if ($url) {
                return new Markup("<script src=\"{$url}\" defer></script>", 'UTF-8');
            }
        } catch (\Throwable $e) {
        }

        // Inline fallback
        $jsPath = dirname(__DIR__, 2) . '/web/assets/storefront/salescheduler.js';
        if (file_exists($jsPath)) {
            $js = file_get_contents($jsPath);
            return new Markup("<script>{$js}</script>", 'UTF-8');
        }

        return new Markup('', 'UTF-8');
    }

    protected function resolvePurchasable($element)
    {
        if ($element instanceof Variant) {
            return $element;
        }

        if ($element instanceof Product) {
            return $element->defaultVariant ?? null;
        }

        return null;
    }

    protected function formatCurrency(float $amount, string $currency): string
    {
        try {
            return Craft::$app->getLocale()->formatCurrency($amount, $currency);
        } catch (\Throwable $e) {
            return '$' . number_format($amount, 2);
        }
    }
}
