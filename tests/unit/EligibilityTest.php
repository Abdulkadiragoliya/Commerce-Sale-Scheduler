<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesalescheduler\services\Eligibility;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;
use DateTime;

class EligibilityTest extends TestCase
{
    public function testActiveAndExpiredDates(): void
    {
        $now = new DateTime('2026-10-12 12:00:00');

        $activeSale = new Sale();
        $activeSale->enabled = true;
        $activeSale->dateFrom = new DateTime('2026-10-10 00:00:00');
        $activeSale->dateTo = new DateTime('2026-10-15 00:00:00');

        $this->assertTrue($activeSale->isActive($now));
        $this->assertFalse($activeSale->isExpired($now));
        $this->assertFalse($activeSale->isScheduled($now));
        $this->assertEquals(Sale::STATUS_ACTIVE, $activeSale->getStatus($now));

        $expiredSale = new Sale();
        $expiredSale->enabled = true;
        $expiredSale->dateFrom = new DateTime('2026-10-01 00:00:00');
        $expiredSale->dateTo = new DateTime('2026-10-05 00:00:00');

        $this->assertFalse($expiredSale->isActive($now));
        $this->assertTrue($expiredSale->isExpired($now));
        $this->assertEquals(Sale::STATUS_EXPIRED, $expiredSale->getStatus($now));

        $futureSale = new Sale();
        $futureSale->enabled = true;
        $futureSale->dateFrom = new DateTime('2026-10-20 00:00:00');
        $futureSale->dateTo = new DateTime('2026-10-25 00:00:00');

        $this->assertFalse($futureSale->isActive($now));
        $this->assertTrue($futureSale->isScheduled($now));
        $this->assertEquals(Sale::STATUS_SCHEDULED, $futureSale->getStatus($now));
    }

    public function testProductExclusionPrecedence(): void
    {
        $eligibility = Plugin::getInstance()->eligibility;

        $sale = new Sale();
        $sale->enabled = true;
        $sale->productIds = [101, 102];
        $sale->exclusionIds = [101]; // 101 is explicitly excluded

        // Test with mock element ID 101
        $rank = $eligibility->evaluateMatch($sale, 101);
        $this->assertNull($rank, 'Excluded product must return null');

        // Test with non-excluded ID 102
        $rank102 = $eligibility->evaluateMatch($sale, 102);
        $this->assertEquals(Eligibility::RANK_PRODUCT, $rank102);
    }
}
