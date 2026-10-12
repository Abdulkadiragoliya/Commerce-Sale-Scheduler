<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * SaleSite ActiveRecord
 *
 * @property int $id
 * @property int $saleId
 * @property int $siteId
 * @property string $dateCreated
 */
class SaleSiteRecord extends ActiveRecord
{
    public const TABLE = '{{%commerce_salescheduler_sale_sites}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function getSale(): ActiveQuery
    {
        return $this->hasOne(SaleRecord::class, ['id' => 'saleId']);
    }
}
