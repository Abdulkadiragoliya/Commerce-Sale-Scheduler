<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * SaleProduct ActiveRecord
 *
 * @property int $id
 * @property int $saleId
 * @property int $productId
 * @property string $dateCreated
 */
class SaleProductRecord extends ActiveRecord
{
    public const TABLE = '{{%commerce_salescheduler_sale_products}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function getSale(): ActiveQuery
    {
        return $this->hasOne(SaleRecord::class, ['id' => 'saleId']);
    }
}
