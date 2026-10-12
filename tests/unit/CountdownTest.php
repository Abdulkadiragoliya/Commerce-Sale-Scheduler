<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;
use DateTime;

class CountdownTest extends TestCase
{
    public function testCountdownRenderingForActiveSale(): void
    {
        $salesService = Plugin::getInstance()->sales;
        $countdown = Plugin::getInstance()->countdown;
        $targetId = $this->getAnyElementId();

        $sale = new Sale();
        $sale->name = 'Countdown Test Sale';
        $sale->countdownEnabled = true;
        $sale->countdownStyle = 'boxed';
        $sale->countdownHeading = 'Flash Deal Ends In';
        $sale->countdownShowDetail = true;
        $sale->enabled = true;
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 20.0;
        $sale->dateTo = (new DateTime('now'))->modify('+3 days');
        $sale->productIds = [$targetId];

        $saved = $salesService->saveSale($sale);
        $this->assertTrue($saved);

        try {
            $html = $countdown->renderCountdown($targetId, ['context' => 'detail']);
            $this->assertStringContains('sale-countdown-timer', $html);
            $this->assertStringContains('sale-countdown--boxed', $html);
            $this->assertStringContains('Flash Deal Ends In', $html);
            $this->assertStringContains('data-countdown-end', $html);
            $this->assertStringContains('data-unit="days"', $html);
            $this->assertStringContains('data-unit="hours"', $html);
            $this->assertStringContains('data-unit="minutes"', $html);
            $this->assertStringContains('data-unit="seconds"', $html);

            // Test non-matching element returns empty string
            $empty = $countdown->renderCountdown($targetId + 999999);
            $this->assertSame('', $empty);
        } finally {
            $salesService->deleteSaleById($sale->id);
        }
    }

    public function testExpiredSaleReturnsEmptyCountdown(): void
    {
        $salesService = Plugin::getInstance()->sales;
        $countdown = Plugin::getInstance()->countdown;
        $targetId = $this->getAnyElementId();

        $sale = new Sale();
        $sale->name = 'Expired Countdown Sale';
        $sale->countdownEnabled = true;
        $sale->enabled = true;
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 20.0;
        $sale->dateTo = (new DateTime('now'))->modify('-2 days'); // Expired
        $sale->productIds = [$targetId];

        $saved = $salesService->saveSale($sale);
        $this->assertTrue($saved);

        try {
            $html = $countdown->renderCountdown($targetId);
            $this->assertSame('', $html, 'Expired sale must never render active countdown timer');
        } finally {
            $salesService->deleteSaleById($sale->id);
        }
    }
}
