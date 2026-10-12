<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;
use DateTime;

class SaleCrudIntegrationTest extends TestCase
{
    public function testDatabaseCrudOperations(): void
    {
        $salesService = Plugin::getInstance()->sales;

        // 1. Create Sale
        $sale = new Sale();
        $sale->name = 'Integration Test Flash Sale';
        $sale->description = 'Created by automated integration test';
        $sale->enabled = true;
        $sale->priority = 5;
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 30.0;
        $sale->dateFrom = new DateTime('now');
        $sale->dateTo = (new DateTime('now'))->modify('+7 days');

        $success = $salesService->saveSale($sale);
        $this->assertTrue($success, 'Sale should save to database successfully');
        $this->assertNotNull($sale->id, 'Saved sale must receive a database primary key');

        $saleId = (int)$sale->id;

        // 2. Read Sale
        $loaded = $salesService->getSaleById($saleId);
        $this->assertNotNull($loaded);
        $this->assertEquals('Integration Test Flash Sale', $loaded->name);
        $this->assertEquals(30.0, $loaded->discountValue);
        $this->assertTrue($loaded->isActive());

        // 3. Update Sale
        $loaded->name = 'Updated Flash Sale Title';
        $loaded->discountValue = 35.0;
        $loaded->priority = 10;
        $updateSuccess = $salesService->saveSale($loaded);
        $this->assertTrue($updateSuccess);

        $reloaded = $salesService->getSaleById($saleId);
        $this->assertEquals('Updated Flash Sale Title', $reloaded->name);
        $this->assertEquals(35.0, $reloaded->discountValue);
        $this->assertEquals(10, $reloaded->priority);

        // 4. Duplicate Sale
        $duplicated = $salesService->duplicateSale($saleId);
        $this->assertNotNull($duplicated);
        $this->assertNotSame($saleId, $duplicated->id);
        $this->assertStringContains('(Copy)', $duplicated->name);
        $this->assertFalse($duplicated->enabled, 'Duplicate must start disabled');

        // 5. Toggle Status
        $toggleSuccess = $salesService->toggleStatus($duplicated->id);
        $this->assertTrue($toggleSuccess);
        $toggled = $salesService->getSaleById($duplicated->id);
        $this->assertTrue($toggled->enabled);

        // 6. Delete Sales
        $delete1 = $salesService->deleteSaleById($saleId);
        $this->assertTrue($delete1);
        $this->assertNull($salesService->getSaleById($saleId));

        $delete2 = $salesService->deleteSaleById($duplicated->id);
        $this->assertTrue($delete2);
        $this->assertNull($salesService->getSaleById($duplicated->id));
    }
}
