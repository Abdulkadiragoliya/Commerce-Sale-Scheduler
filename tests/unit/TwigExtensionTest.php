<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use Craft;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use abdulkadiragoliya\commercesalescheduler\web\twig\Extension;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;

class TwigExtensionTest extends TestCase
{
    public function testTwigExtensionHelpers(): void
    {
        $extension = new Extension();
        $salesService = Plugin::getInstance()->sales;
        $targetId = $this->getAnyElementId();

        $sale = new Sale();
        $sale->name = 'Twig Test Sale';
        $sale->discountType = Sale::TYPE_PERCENTAGE;
        $sale->discountValue = 25.0;
        $sale->tagText = '25% OFF';
        $sale->enabled = true;
        $sale->productIds = [$targetId];

        $salesService->saveSale($sale);

        try {
            // CSS & JS helpers
            $css = (string)$extension->css();
            $this->assertStringContains('salescheduler.css', $css);

            $js = (string)$extension->js();
            $this->assertStringContains('salescheduler.js', $js);

            // Tag helper
            $tagHtml = (string)$extension->tag($targetId);
            $this->assertStringContains('sale-tag-badge', $tagHtml);
            $this->assertStringContains('25% OFF', $tagHtml);

            // getSale helper
            $resolvedSale = $extension->getSale($targetId);
            $this->assertNotNull($resolvedSale);
            $this->assertEquals('Twig Test Sale', $resolvedSale->name);

            // getSaleInfo helper
            $info = $extension->getSaleInfo($targetId);
            $this->assertTrue(is_array($info));
            $this->assertTrue(isset($info['hasSale']));

            // Full saleBlock helper
            $blockHtml = (string)$extension->saleBlock($targetId);
            $this->assertStringContains('sale-scheduler-block', $blockHtml);
        } finally {
            $salesService->deleteSaleById($sale->id);
        }
    }
}
