<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;
use DateTime;

class SaleModelTest extends TestCase
{
    public function testValidationRules(): void
    {
        $sale = new Sale();
        $this->assertFalse($sale->validate(), 'Empty sale should not validate');
        $this->assertTrue($sale->hasErrors('name'));

        // Invalid percentage > 100
        $sale->name = 'Summer Sale';
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 120.0;
        $this->assertFalse($sale->validate());
        $this->assertTrue($sale->hasErrors('discountValue'));

        // Negative discount
        $sale->discountValue = -10.0;
        $this->assertFalse($sale->validate());
        $this->assertTrue($sale->hasErrors('discountValue'));

        // End date before start date
        $sale->discountValue = 20.0;
        $sale->dateFrom = new DateTime('2026-10-15');
        $sale->dateTo = new DateTime('2026-10-10');
        $this->assertFalse($sale->validate());
        $this->assertTrue($sale->hasErrors('dateTo'));

        // Valid setup
        $sale->dateTo = new DateTime('2026-10-20');
        $this->assertTrue($sale->validate(), 'Valid sale must pass validation');
    }

    public function testFormattedDiscount(): void
    {
        $sale = new Sale();
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 15.0;
        $this->assertEquals('15%', $sale->getFormattedDiscount());
    }
}
