<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;

class TagManagerTest extends TestCase
{
    public function testTagRenderingWithTextFallback(): void
    {
        $salesService = Plugin::getInstance()->sales;
        $tagManager = Plugin::getInstance()->tagManager;
        $targetId = $this->getAnyElementId();

        $sale = new Sale();
        $sale->name = 'Tag Test Sale';
        $sale->tagText = '30% OFF';
        $sale->tagEnabled = true;
        $sale->tagPositionListing = 'top-right';
        $sale->tagPositionDetail = 'top-left';
        $sale->tagWidth = '75px';
        $sale->enabled = true;
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 30.0;
        $sale->productIds = [$targetId];

        $saved = $salesService->saveSale($sale);
        $this->assertTrue($saved);

        try {
            // Render in listing context
            $listingHtml = $tagManager->renderTag($targetId, ['context' => 'listing']);
            $this->assertStringContains('sale-tag-badge', $listingHtml);
            $this->assertStringContains('sale-tag-badge--top-right', $listingHtml);
            $this->assertStringContains('30% OFF', $listingHtml);

            // Render in detail context
            $detailHtml = $tagManager->renderTag($targetId, ['context' => 'detail']);
            $this->assertStringContains('sale-tag-badge--top-left', $detailHtml);

            // Non-matching element returns empty string
            $emptyHtml = $tagManager->renderTag($targetId + 999999);
            $this->assertSame('', $emptyHtml);
        } finally {
            $salesService->deleteSaleById($sale->id);
        }
    }
}
