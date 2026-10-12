<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\services;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;

/**
 * Cache Management Service
 */
class CacheService extends Component
{
    public const CACHE_TAG = 'commerce_sale_scheduler';

    /**
     * Invalidate all sale-related caches
     */
    public function invalidateAll(): void
    {
        try {
            Craft::$app->getCache()->delete(self::CACHE_TAG);
            Craft::$app->getTemplateCaches()->deleteCachesByTag(self::CACHE_TAG);
        } catch (\Throwable $e) {
            Craft::warning('Failed invalidating sale scheduler cache: ' . $e->getMessage(), __METHOD__);
        }
    }
}
