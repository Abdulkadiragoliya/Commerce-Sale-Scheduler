<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * SaleCategory ActiveRecord
 *
 * @property int $id
 * @property int $saleId
 * @property int $categoryId
 * @property string $dateCreated
 */
class SaleCategoryRecord extends ActiveRecord
{
    public const TABLE = '{{%commerce_salescheduler_sale_categories}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function getSale(): ActiveQuery
    {
        return $this->hasOne(SaleRecord::class, ['id' => 'saleId']);
    }
}
