<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\migrations;

use Craft;
use craft\db\Migration;

/**
 * Installation Migration for Commerce Sale Scheduler
 */
class Install extends Migration
{
    public const TABLE_SALES = '{{%commerce_salescheduler_sales}}';
    public const TABLE_SALE_PRODUCTS = '{{%commerce_salescheduler_sale_products}}';
    public const TABLE_SALE_VARIANTS = '{{%commerce_salescheduler_sale_variants}}';
    public const TABLE_SALE_CATEGORIES = '{{%commerce_salescheduler_sale_categories}}';
    public const TABLE_SALE_EXCLUSIONS = '{{%commerce_salescheduler_sale_exclusions}}';
    public const TABLE_SALE_SITES = '{{%commerce_salescheduler_sale_sites}}';

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createSalesTable();
        $this->createSaleProductsTable();
        $this->createSaleVariantsTable();
        $this->createSaleCategoriesTable();
        $this->createSaleExclusionsTable();
        $this->createSaleSitesTable();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(self::TABLE_SALE_SITES);
        $this->dropTableIfExists(self::TABLE_SALE_EXCLUSIONS);
        $this->dropTableIfExists(self::TABLE_SALE_CATEGORIES);
        $this->dropTableIfExists(self::TABLE_SALE_VARIANTS);
        $this->dropTableIfExists(self::TABLE_SALE_PRODUCTS);
        $this->dropTableIfExists(self::TABLE_SALES);

        return true;
    }

    protected function createSalesTable(): void
    {
        if ($this->db->tableExists(self::TABLE_SALES)) {
            return;
        }

        $this->createTable(self::TABLE_SALES, [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'priority' => $this->integer()->defaultValue(0)->notNull(),
            'discountType' => $this->string(32)->defaultValue('percentage')->notNull(),
            'discountValue' => $this->decimal(14, 4)->defaultValue(0)->notNull(),
            'dateFrom' => $this->dateTime(),
            'dateTo' => $this->dateTime(),
            'applyToAllVariants' => $this->boolean()->defaultValue(true)->notNull(),
            'allSites' => $this->boolean()->defaultValue(true)->notNull(),
            'tagAssetId' => $this->integer(),
            'tagText' => $this->string(100),
            'tagEnabled' => $this->boolean()->defaultValue(true)->notNull(),
            'tagPositionListing' => $this->string(32)->defaultValue('top-right')->notNull(),
            'tagPositionDetail' => $this->string(32)->defaultValue('top-right')->notNull(),
            'tagWidth' => $this->string(32)->defaultValue('80px'),
            'tagCustomClass' => $this->string(255),
            'countdownEnabled' => $this->boolean()->defaultValue(true)->notNull(),
            'countdownStyle' => $this->string(32)->defaultValue('compact')->notNull(),
            'countdownHeading' => $this->string(255),
            'countdownShowListing' => $this->boolean()->defaultValue(false)->notNull(),
            'countdownShowDetail' => $this->boolean()->defaultValue(true)->notNull(),
            'countdownCustomClass' => $this->string(255),
            'priceShowOriginal' => $this->boolean()->defaultValue(true)->notNull(),
            'priceShowSavings' => $this->boolean()->defaultValue(true)->notNull(),
            'priceShowBadge' => $this->boolean()->defaultValue(true)->notNull(),
            'badgeText' => $this->string(100),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(null, self::TABLE_SALES, ['enabled'], false);
        $this->createIndex(null, self::TABLE_SALES, ['priority'], false);
        $this->createIndex(null, self::TABLE_SALES, ['dateFrom'], false);
        $this->createIndex(null, self::TABLE_SALES, ['dateTo'], false);

        if ($this->db->tableExists('{{%elements}}')) {
            $this->addForeignKey(
                null,
                self::TABLE_SALES,
                ['tagAssetId'],
                '{{%elements}}',
                ['id'],
                'SET NULL',
                'CASCADE'
            );
        }
    }

    protected function createSaleProductsTable(): void
    {
        if ($this->db->tableExists(self::TABLE_SALE_PRODUCTS)) {
            return;
        }

        $this->createTable(self::TABLE_SALE_PRODUCTS, [
            'id' => $this->primaryKey(),
            'saleId' => $this->integer()->notNull(),
            'productId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(null, self::TABLE_SALE_PRODUCTS, ['saleId', 'productId'], true);

        $this->addForeignKey(
            null,
            self::TABLE_SALE_PRODUCTS,
            ['saleId'],
            self::TABLE_SALES,
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->tableExists('{{%elements}}')) {
            $this->addForeignKey(
                null,
                self::TABLE_SALE_PRODUCTS,
                ['productId'],
                '{{%elements}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }
    }

    protected function createSaleVariantsTable(): void
    {
        if ($this->db->tableExists(self::TABLE_SALE_VARIANTS)) {
            return;
        }

        $this->createTable(self::TABLE_SALE_VARIANTS, [
            'id' => $this->primaryKey(),
            'saleId' => $this->integer()->notNull(),
            'variantId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(null, self::TABLE_SALE_VARIANTS, ['saleId', 'variantId'], true);

        $this->addForeignKey(
            null,
            self::TABLE_SALE_VARIANTS,
            ['saleId'],
            self::TABLE_SALES,
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->tableExists('{{%elements}}')) {
            $this->addForeignKey(
                null,
                self::TABLE_SALE_VARIANTS,
                ['variantId'],
                '{{%elements}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }
    }

    protected function createSaleCategoriesTable(): void
    {
        if ($this->db->tableExists(self::TABLE_SALE_CATEGORIES)) {
            return;
        }

        $this->createTable(self::TABLE_SALE_CATEGORIES, [
            'id' => $this->primaryKey(),
            'saleId' => $this->integer()->notNull(),
            'categoryId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(null, self::TABLE_SALE_CATEGORIES, ['saleId', 'categoryId'], true);

        $this->addForeignKey(
            null,
            self::TABLE_SALE_CATEGORIES,
            ['saleId'],
            self::TABLE_SALES,
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->tableExists('{{%elements}}')) {
            $this->addForeignKey(
                null,
                self::TABLE_SALE_CATEGORIES,
                ['categoryId'],
                '{{%elements}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }
    }

    protected function createSaleExclusionsTable(): void
    {
        if ($this->db->tableExists(self::TABLE_SALE_EXCLUSIONS)) {
            return;
        }

        $this->createTable(self::TABLE_SALE_EXCLUSIONS, [
            'id' => $this->primaryKey(),
            'saleId' => $this->integer()->notNull(),
            'productId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(null, self::TABLE_SALE_EXCLUSIONS, ['saleId', 'productId'], true);

        $this->addForeignKey(
            null,
            self::TABLE_SALE_EXCLUSIONS,
            ['saleId'],
            self::TABLE_SALES,
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->tableExists('{{%elements}}')) {
            $this->addForeignKey(
                null,
                self::TABLE_SALE_EXCLUSIONS,
                ['productId'],
                '{{%elements}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }
    }

    protected function createSaleSitesTable(): void
    {
        if ($this->db->tableExists(self::TABLE_SALE_SITES)) {
            return;
        }

        $this->createTable(self::TABLE_SALE_SITES, [
            'id' => $this->primaryKey(),
            'saleId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(null, self::TABLE_SALE_SITES, ['saleId', 'siteId'], true);

        $this->addForeignKey(
            null,
            self::TABLE_SALE_SITES,
            ['saleId'],
            self::TABLE_SALES,
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->tableExists('{{%sites}}')) {
            $this->addForeignKey(
                null,
                self::TABLE_SALE_SITES,
                ['siteId'],
                '{{%sites}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }
    }
}
