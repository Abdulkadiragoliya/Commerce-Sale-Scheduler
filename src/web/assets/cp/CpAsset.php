<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

/**
 * Control Panel Asset Bundle for Commerce Sale Scheduler
 */
class CpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__;

        $this->depends = [
            CraftCpAsset::class,
        ];

        $this->css = [
            'cp.css',
        ];

        $this->js = [
            'cp.js',
        ];

        parent::init();
    }
}
