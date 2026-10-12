<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * SaleVariant ActiveRecord
 *
 * @property int $id
 * @property int $saleId
 * @property int $variantId
 * @property string $dateCreated
 */
class SaleVariantRecord extends ActiveRecord
{
    public const TABLE = '{{%commerce_salescheduler_sale_variants}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function getSale(): ActiveQuery
    {
        return $this->hasOne(SaleRecord::class, ['id' => 'saleId']);
    }
}
