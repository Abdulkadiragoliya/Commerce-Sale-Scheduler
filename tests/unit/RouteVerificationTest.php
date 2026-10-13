<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use Craft;
use abdulkadiragoliya\commercesalescheduler\controllers\DashboardController;
use abdulkadiragoliya\commercesalescheduler\controllers\SalesController;
use abdulkadiragoliya\commercesalescheduler\controllers\SettingsController;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;

class RouteVerificationTest extends TestCase
{
    public function testRouteInventoryAndControllers(): void
    {
        // 1. Controller instantiation
        $dashboardController = new DashboardController('dashboard', Craft::$app);
        $this->assertNotNull($dashboardController);
        $this->assertTrue(method_exists($dashboardController, 'actionIndex'));

        $salesController = new SalesController('sales', Craft::$app);
        $this->assertNotNull($salesController);
        $this->assertTrue(method_exists($salesController, 'actionIndex'));
        $this->assertTrue(method_exists($salesController, 'actionEdit'));
        $this->assertTrue(method_exists($salesController, 'actionSave'));
        $this->assertTrue(method_exists($salesController, 'actionDuplicate'));
        $this->assertTrue(method_exists($salesController, 'actionDelete'));
        $this->assertTrue(method_exists($salesController, 'actionToggleStatus'));
        $this->assertTrue(method_exists($salesController, 'actionBulkAction'));
        $this->assertTrue(method_exists($salesController, 'actionPreview'));

        $settingsController = new SettingsController('settings', Craft::$app);
        $this->assertNotNull($settingsController);
        $this->assertTrue(method_exists($settingsController, 'actionIndex'));
        $this->assertTrue(method_exists($settingsController, 'actionSave'));
    }

    public function testTemplateResolutionAndRendering(): void
    {
        $view = Craft::$app->getView();
        $salesService = \abdulkadiragoliya\commercesalescheduler\Plugin::getInstance()->sales;
        $settings = \abdulkadiragoliya\commercesalescheduler\Plugin::getInstance()->getSettings();

        // 1. Dashboard template render test
        $dashHtml = $view->renderTemplate('commerce-sale-scheduler/dashboard/index', [
            'stats' => $salesService->getDashboardStats(),
            'recentSales' => [],
            'conflicts' => [],
            'title' => 'Dashboard Test',
        ]);
        $this->assertStringContains('Sale Scheduler Dashboard', $dashHtml);
        $this->assertStringContains('Total Sales', $dashHtml);

        // 2. Sales listing template render test
        $listingHtml = $view->renderTemplate('commerce-sale-scheduler/sales/index', [
            'sales' => [],
            'search' => '',
            'status' => 'all',
            'title' => 'Sales Listing Test',
        ]);
        $this->assertStringContains('Scheduled Sales', $listingHtml);

        // 3. Sale edit template render test
        $newSale = new \abdulkadiragoliya\commercesalescheduler\models\Sale();
        $editHtml = $view->renderTemplate('commerce-sale-scheduler/sales/_edit', [
            'sale' => $newSale,
            'isNew' => true,
            'affectedCounts' => ['products' => 0, 'variants' => 0],
            'title' => 'Create Sale Test',
        ]);
        $this->assertStringContains('General Details', $editHtml);
        $this->assertStringContains('Discount Configuration', $editHtml);
        $this->assertStringContains('Tag &amp; Badge Settings', $editHtml);
        $this->assertStringContains('Countdown Timer Settings', $editHtml);

        // 4. Settings template render test
        $settingsHtml = $view->renderTemplate('commerce-sale-scheduler/settings/index', [
            'settings' => $settings,
            'title' => 'Settings Test',
        ]);
        $this->assertStringContains('Sale Scheduler Settings', $settingsHtml);
        $this->assertStringContains('Default Promotional Tag Settings', $settingsHtml);
    }

    public function testCpNavigationIntegrity(): void
    {
        $plugin = \abdulkadiragoliya\commercesalescheduler\Plugin::getInstance();
        $navItem = $plugin->getCpNavItem();

        $this->assertNotNull($navItem, 'CP Nav item must exist');
        $this->assertEquals('Sale Scheduler', $navItem['label']);
        $this->assertEquals('sale-scheduler', $navItem['url']);
        $this->assertTrue(isset($navItem['subnav']));
        $this->assertTrue(isset($navItem['subnav']['dashboard']));
        $this->assertTrue(isset($navItem['subnav']['sales']));
        $this->assertTrue(isset($navItem['subnav']['settings']));
        $this->assertEquals('sale-scheduler/dashboard', $navItem['subnav']['dashboard']['url']);
        $this->assertEquals('sale-scheduler/sales', $navItem['subnav']['sales']['url']);
        $this->assertEquals('sale-scheduler/settings', $navItem['subnav']['settings']['url']);
    }
}
