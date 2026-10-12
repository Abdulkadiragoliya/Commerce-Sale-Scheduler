<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests\unit;

use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Settings;
use abdulkadiragoliya\commercesaleschedulertests\TestCase;

class SettingsTest extends TestCase
{
    public function testDefaultSettingsValues(): void
    {
        $settings = new Settings();

        $this->assertEquals('compact', $settings->defaultCountdownStyle);
        $this->assertEquals('top-right', $settings->defaultTagPositionListing);
        $this->assertEquals('top-right', $settings->defaultTagPositionDetail);
        $this->assertTrue($settings->defaultTagEnabled);
        $this->assertTrue($settings->defaultCountdownEnabled);
        $this->assertTrue($settings->defaultPriceShowOriginal);
        $this->assertTrue($settings->defaultPriceShowSavings);
        $this->assertEquals(300, $settings->cacheTtl);
        $this->assertTrue($settings->validate());
    }

    public function testPluginSettingsInstance(): void
    {
        $pluginSettings = Plugin::getInstance()->getSettings();
        $this->assertNotNull($pluginSettings);
        $this->assertTrue($pluginSettings instanceof Settings);
    }
}
