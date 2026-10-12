<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * Sale ActiveRecord
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $enabled
 * @property int $priority
 * @property string $discountType
 * @property float $discountValue
 * @property string|null $dateFrom
 * @property string|null $dateTo
 * @property bool $applyToAllVariants
 * @property bool $allSites
 * @property int|null $tagAssetId
 * @property string|null $tagText
 * @property bool $tagEnabled
 * @property string $tagPositionListing
 * @property string $tagPositionDetail
 * @property string|null $tagWidth
 * @property string|null $tagCustomClass
 * @property bool $countdownEnabled
 * @property string $countdownStyle
 * @property string|null $countdownHeading
 * @property bool $countdownShowListing
 * @property bool $countdownShowDetail
 * @property string|null $countdownCustomClass
 * @property bool $priceShowOriginal
 * @property bool $priceShowSavings
 * @property bool $priceShowBadge
 * @property string|null $badgeText
 * @property string $uid
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class SaleRecord extends ActiveRecord
{
    public const TABLE = '{{%commerce_salescheduler_sales}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function getProducts(): ActiveQuery
    {
        return $this->hasMany(SaleProductRecord::class, ['saleId' => 'id']);
    }

    public function getVariants(): ActiveQuery
    {
        return $this->hasMany(SaleVariantRecord::class, ['saleId' => 'id']);
    }

    public function getCategories(): ActiveQuery
    {
        return $this->hasMany(SaleCategoryRecord::class, ['saleId' => 'id']);
    }

    public function getExclusions(): ActiveQuery
    {
        return $this->hasMany(SaleExclusionRecord::class, ['saleId' => 'id']);
    }

    public function getSites(): ActiveQuery
    {
        return $this->hasMany(SaleSiteRecord::class, ['saleId' => 'id']);
    }
}
