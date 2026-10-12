<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\services;

use Craft;
use craft\base\Component;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesalescheduler\models\SaleCalculation;

/**
 * Discount Engine Service
 */
class DiscountEngine extends Component
{
    /**
     * Calculate discounted pricing for a given original price and sale
     */
    public function calculate(float $originalPrice, Sale $sale, ?string $currency = null): SaleCalculation
    {
        $calculation = new SaleCalculation();
        $calculation->originalPrice = $originalPrice;
        $calculation->currency = $currency ?? $this->getStoreCurrency();
        $calculation->sale = $sale;

        if ($originalPrice <= 0) {
            $calculation->salePrice = 0.0;
            $calculation->savingsAmount = 0.0;
            $calculation->savingsPercent = 0.0;
            $calculation->isSaleApplied = false;
            return $calculation;
        }

        if ($sale->discountType === Sale::TYPE_PERCENTAGE) {
            $percent = min(100.0, max(0.0, (float)$sale->discountValue));
            $savings = round($originalPrice * ($percent / 100.0), 2);
            $salePrice = max(0.0, round($originalPrice - $savings, 2));
            $savingsPercent = $percent;
        } elseif ($sale->discountType === Sale::TYPE_FIXED) {
            $fixed = max(0.0, (float)$sale->discountValue);
            $savings = min($originalPrice, round($fixed, 2));
            $salePrice = max(0.0, round($originalPrice - $savings, 2));
            $savingsPercent = $originalPrice > 0 ? round(($savings / $originalPrice) * 100.0, 1) : 0.0;
        } else {
            $salePrice = $originalPrice;
            $savings = 0.0;
            $savingsPercent = 0.0;
        }

        $calculation->salePrice = $salePrice;
        $calculation->savingsAmount = $savings;
        $calculation->savingsPercent = $savingsPercent;
        $calculation->isSaleApplied = ($savings > 0);

        return $calculation;
    }

    /**
     * Get primary store currency
     */
    public function getStoreCurrency(): string
    {
        try {
            if (Craft::$app->has('commerce')) {
                return \craft\commerce\Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();
            }
        } catch (\Throwable $e) {
        }

        return 'USD';
    }
}
