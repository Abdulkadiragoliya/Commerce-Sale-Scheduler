<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\models;

use craft\base\Model;

/**
 * Commerce Sale Scheduler Settings Model
 */
class Settings extends Model
{
    public string $defaultCountdownStyle = 'compact';
    public string $defaultTagPositionListing = 'top-right';
    public string $defaultTagPositionDetail = 'top-right';
    public string $defaultTagWidth = '80px';
    public bool $defaultTagEnabled = true;
    public bool $defaultCountdownEnabled = true;
    public bool $defaultCountdownShowListing = false;
    public bool $defaultCountdownShowDetail = true;
    public bool $defaultPriceShowOriginal = true;
    public bool $defaultPriceShowSavings = true;
    public bool $defaultPriceShowBadge = true;
    public bool $enableListingTimers = false;
    public int $cacheTtl = 300;

    public string $primaryCountdownLabelDays = 'Days';
    public string $primaryCountdownLabelHours = 'Hours';
    public string $primaryCountdownLabelMinutes = 'Mins';
    public string $primaryCountdownLabelSeconds = 'Secs';

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['defaultCountdownStyle'], 'in', 'range' => ['compact', 'boxed', 'minimal']],
            [['defaultTagPositionListing', 'defaultTagPositionDetail'], 'in', 'range' => ['top-left', 'top-right', 'bottom-left', 'bottom-right']],
            [['defaultTagWidth'], 'string', 'max' => 32],
            [['defaultTagEnabled', 'defaultCountdownEnabled', 'defaultCountdownShowListing', 'defaultCountdownShowDetail', 'defaultPriceShowOriginal', 'defaultPriceShowSavings', 'defaultPriceShowBadge', 'enableListingTimers'], 'boolean'],
            [['cacheTtl'], 'integer', 'min' => 0],
            [['primaryCountdownLabelDays', 'primaryCountdownLabelHours', 'primaryCountdownLabelMinutes', 'primaryCountdownLabelSeconds'], 'string', 'max' => 32],
        ];
    }
}
