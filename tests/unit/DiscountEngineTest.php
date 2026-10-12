<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;

class DiscountEngineTest extends TestCase
{
    public function testPercentageDiscountCalculation(): void
    {
        $engine = Plugin::getInstance()->discounts;

        $sale = new Sale();
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 25.0;

        $calc = $engine->calculate(100.0, $sale, 'USD');

        $this->assertEquals(75.0, $calc->salePrice);
        $this->assertEquals(25.0, $calc->savingsAmount);
        $this->assertEquals(25.0, $calc->savingsPercent);
        $this->assertTrue($calc->isSaleApplied);
    }

    public function testFixedDiscountCalculation(): void
    {
        $engine = Plugin::getInstance()->discounts;

        $sale = new Sale();
        $sale->discountType = Sale::TYPE_FIXED;
        $sale->discountValue = 15.0;

        $calc = $engine->calculate(80.0, $sale, 'USD');

        $this->assertEquals(65.0, $calc->salePrice);
        $this->assertEquals(15.0, $calc->savingsAmount);
        $this->assertEquals(18.8, $calc->savingsPercent);
        $this->assertTrue($calc->isSaleApplied);
    }

    public function testFixedDiscountExceedingProductPrice(): void
    {
        $engine = Plugin::getInstance()->discounts;

        $sale = new Sale();
        $sale->discountType = Sale::TYPE_FIXED;
        $sale->discountValue = 50.0;

        // $50 off a $30 product should cap at $30 savings, price = $0.0 (never negative)
        $calc = $engine->calculate(30.0, $sale, 'USD');

        $this->assertEquals(0.0, $calc->salePrice);
        $this->assertEquals(30.0, $calc->savingsAmount);
        $this->assertEquals(100.0, $calc->savingsPercent);
        $this->assertTrue($calc->isSaleApplied);
    }

    public function testZeroOrNegativeOriginalPrice(): void
    {
        $engine = Plugin::getInstance()->discounts;

        $sale = new Sale();
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 20.0;

        $calc = $engine->calculate(0.0, $sale, 'USD');
        $this->assertEquals(0.0, $calc->salePrice);
        $this->assertEquals(0.0, $calc->savingsAmount);
        $this->assertFalse($calc->isSaleApplied);
    }
}
