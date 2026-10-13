<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\models;

use Craft;
use craft\base\Model;
use craft\elements\Asset;
use craft\helpers\DateTimeHelper;
use DateTime;
use DateTimeInterface;

/**
 * Sale Model
 */
class Sale extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DISABLED = 'disabled';

    public ?int $id = null;
    public string $name = '';
    public ?string $description = null;
    public bool $enabled = true;
    public int $priority = 0;
    public string $discountType = self::TYPE_PERCENTAGE;
    public float $discountValue = 0.0;
    public ?DateTime $dateFrom = null;
    public ?DateTime $dateTo = null;
    public bool $applyToAllVariants = true;
    public bool $allSites = true;

    /** @var int[] */
    public array $productIds = [];

    /** @var int[] */
    public array $variantIds = [];

    /** @var int[] */
    public array $categoryIds = [];

    /** @var int[] */
    public array $exclusionIds = [];

    /** @var int[] */
    public array $siteIds = [];

    public ?int $tagAssetId = null;
    public ?string $tagText = null;
    public bool $tagEnabled = true;
    public string $tagPositionListing = 'top-right';
    public string $tagPositionDetail = 'top-right';
    public string $tagWidth = '80px';
    public ?string $tagCustomClass = null;

    public bool $countdownEnabled = true;
    public string $countdownStyle = 'compact';
    public ?string $countdownHeading = null;
    public bool $countdownShowListing = false;
    public bool $countdownShowDetail = true;
    public ?string $countdownHeadingColor = null;
    public ?string $countdownDigitsColor = null;
    public ?string $countdownCustomClass = null;

    public bool $priceShowOriginal = true;
    public bool $priceShowSavings = true;
    public bool $priceShowBadge = true;
    public ?string $badgeText = null;

    public ?string $uid = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name', 'discountType', 'discountValue'], 'required'],
            ['name', 'string', 'max' => 255],
            ['description', 'string'],
            [['enabled', 'applyToAllVariants', 'allSites', 'tagEnabled', 'countdownEnabled', 'countdownShowListing', 'countdownShowDetail', 'priceShowOriginal', 'priceShowSavings', 'priceShowBadge'], 'boolean'],
            ['priority', 'integer'],
            ['discountType', 'in', 'range' => [self::TYPE_PERCENTAGE, self::TYPE_FIXED]],
            ['discountValue', 'number', 'min' => 0],
            ['discountValue', 'validateDiscountValue'],
            [['dateFrom', 'dateTo'], 'validateDates'],
            [['tagPositionListing', 'tagPositionDetail'], 'in', 'range' => ['top-left', 'top-right', 'bottom-left', 'bottom-right']],
            ['countdownStyle', 'in', 'range' => ['compact', 'boxed', 'minimal']],
            [['tagWidth'], 'string', 'max' => 32],
            [['tagText', 'badgeText'], 'string', 'max' => 100],
            [['countdownHeadingColor', 'countdownDigitsColor'], 'string', 'max' => 32],
            [['tagCustomClass', 'countdownHeading', 'countdownCustomClass'], 'string', 'max' => 255],
            [['tagAssetId'], 'integer'],
            [['productIds', 'variantIds', 'categoryIds', 'exclusionIds', 'siteIds'], 'each', 'rule' => ['integer']],
        ];
    }

    public function validateDiscountValue(string $attribute): void
    {
        if ($this->discountType === self::TYPE_PERCENTAGE && $this->discountValue > 100) {
            $this->addError($attribute, Craft::t('commerce-sale-scheduler', 'Percentage discount cannot exceed 100%.'));
        }
    }

    public function validateDates(string $attribute): void
    {
        if ($this->dateFrom && $this->dateTo) {
            if ($this->dateTo < $this->dateFrom) {
                $this->addError('dateTo', Craft::t('commerce-sale-scheduler', 'End date must be after or equal to the start date.'));
            }
        }
    }

    /**
     * Check if sale is currently active
     */
    public function isActive(?DateTimeInterface $now = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $now = $now ?? new DateTime('now');

        if ($this->dateFrom && $now < $this->dateFrom) {
            return false;
        }

        if ($this->dateTo && $now > $this->dateTo) {
            return false;
        }

        return true;
    }

    /**
     * Check if sale is scheduled for the future
     */
    public function isScheduled(?DateTimeInterface $now = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $now = $now ?? new DateTime('now');

        return $this->dateFrom !== null && $now < $this->dateFrom;
    }

    /**
     * Check if sale is expired
     */
    public function isExpired(?DateTimeInterface $now = null): bool
    {
        $now = $now ?? new DateTime('now');

        return $this->dateTo !== null && $now > $this->dateTo;
    }

    /**
     * Get sale status key
     */
    public function getStatus(?DateTimeInterface $now = null): string
    {
        if ($this->isExpired($now)) {
            return self::STATUS_EXPIRED;
        }

        if (!$this->enabled) {
            return self::STATUS_DISABLED;
        }

        if ($this->isScheduled($now)) {
            return self::STATUS_SCHEDULED;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Human readable status label
     */
    public function getStatusLabel(?DateTimeInterface $now = null): string
    {
        return match ($this->getStatus($now)) {
            self::STATUS_ACTIVE => Craft::t('commerce-sale-scheduler', 'Active'),
            self::STATUS_SCHEDULED => Craft::t('commerce-sale-scheduler', 'Scheduled'),
            self::STATUS_EXPIRED => Craft::t('commerce-sale-scheduler', 'Expired'),
            self::STATUS_DISABLED => Craft::t('commerce-sale-scheduler', 'Disabled'),
            default => Craft::t('commerce-sale-scheduler', 'Unknown'),
        };
    }

    /**
     * Get tag asset element
     */
    public function getTagAsset(): ?Asset
    {
        if (!$this->tagAssetId) {
            return null;
        }

        return Craft::$app->getAssets()->getAssetById($this->tagAssetId);
    }

    /**
     * Formatted discount string, e.g. "20%" or "$15.00"
     */
    public function getFormattedDiscount(): string
    {
        if ($this->discountType === self::TYPE_PERCENTAGE) {
            return (float)$this->discountValue . '%';
        }

        try {
            $currency = Craft::$app->has('commerce') 
                ? \craft\commerce\Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso() 
                : 'USD';
            return Craft::$app->getLocale()->formatCurrency($this->discountValue, $currency);
        } catch (\Throwable $e) {
            return '$' . number_format($this->discountValue, 2);
        }
    }
}
