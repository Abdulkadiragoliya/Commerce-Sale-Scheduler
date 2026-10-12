<?php
declare(strict_types=1);

require 'C:/wamp64/www/craftLearning/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

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

return $app;
