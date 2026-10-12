<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\models;

use Craft;
use craft\base\Model;

/**
 * Sale Calculation Value Object
 */
class SaleCalculation extends Model
{
    public float $originalPrice = 0.0;
    public float $salePrice = 0.0;
    public float $savingsAmount = 0.0;
    public float $savingsPercent = 0.0;
    public string $currency = 'USD';
    public ?Sale $sale = null;
    public bool $isSaleApplied = false;

    public function getFormattedOriginalPrice(): string
    {
        return $this->formatPrice($this->originalPrice);
    }

    public function getFormattedSalePrice(): string
    {
        return $this->formatPrice($this->salePrice);
    }

    public function getFormattedSavingsAmount(): string
    {
        return $this->formatPrice($this->savingsAmount);
    }

    public function getFormattedSavingsPercent(): string
    {
        return round($this->savingsPercent, 1) . '%';
    }

    protected function formatPrice(float $amount): string
    {
        try {
            return Craft::$app->getLocale()->formatCurrency($amount, $this->currency);
        } catch (\Throwable $e) {
            return '$' . number_format($amount, 2);
        }
    }
}
