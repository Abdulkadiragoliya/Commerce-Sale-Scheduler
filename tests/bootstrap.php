<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);
if (file_exists($root . '/craft')) {
    if (!defined('CRAFT_BASE_PATH')) {
        define('CRAFT_BASE_PATH', $root);
    }
    if (!defined('CRAFT_VENDOR_PATH')) {
        define('CRAFT_VENDOR_PATH', CRAFT_BASE_PATH . '/vendor');
    }
    require_once CRAFT_VENDOR_PATH . '/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists(CRAFT_BASE_PATH . '/.env')) {
        Dotenv\Dotenv::create(CRAFT_BASE_PATH)->load();
    }
    if (!defined('CRAFT_ENVIRONMENT')) {
        define('CRAFT_ENVIRONMENT', getenv('ENVIRONMENT') ?: 'production');
    }
    $app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
} else {
    require 'C:/wamp64/www/craftLearning/bootstrap.php';
    $app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
}

// Enable mock web components for CP template rendering during tests
if (!Craft::$app->has('session')) {
    Craft::$app->set('session', [
        'class' => \yii\web\Session::class,
    ]);
}

Craft::$app->set('response', [
    'class' => \craft\web\Response::class,
]);

Craft::$app->set('request', [
    'class' => \craft\web\Request::class,
    'enableCsrfValidation' => false,
    'cookieValidationKey' => 'commerce-sale-scheduler-testing-secret-key-123',
]);

$assetDir = sys_get_temp_dir() . '/craft_test_assets';
if (!is_dir($assetDir)) {
    @mkdir($assetDir, 0777, true);
}
Craft::$app->getAssetManager()->basePath = $assetDir;
Craft::$app->getAssetManager()->baseUrl = '/test-assets';

try {
    $db = Craft::$app->getDb();
    $schema = $db->getTableSchema('{{%commerce_salescheduler_sales}}');
    if ($schema) {
        if (!isset($schema->columns['countdownHeadingColor'])) {
            $db->createCommand()->addColumn('{{%commerce_salescheduler_sales}}', 'countdownHeadingColor', 'varchar(32) null')->execute();
        }
        if (!isset($schema->columns['countdownDigitsColor'])) {
            $db->createCommand()->addColumn('{{%commerce_salescheduler_sales}}', 'countdownDigitsColor', 'varchar(32) null')->execute();
        }
    }
} catch (\Throwable $e) {}

return $app;
