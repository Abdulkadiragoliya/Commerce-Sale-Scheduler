<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\controllers;

use Craft;
use craft\web\Controller;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\web\assets\cp\CpAsset;
use yii\web\Response;

/**
 * Control Panel Dashboard Controller
 */
class DashboardController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->requireCpRequest();
        $this->requirePermission('commerce-sale-scheduler:manage');
        Craft::$app->getView()->registerAssetBundle(CpAsset::class);
        return parent::beforeAction($action);
    }

    /**
     * Renders the Sale Scheduler dashboard
     */
    public function actionIndex(): Response
    {
        $salesService = Plugin::getInstance()->sales;
        $stats = $salesService->getDashboardStats();
        $recentSales = $salesService->getAllSales(['limit' => 5, 'orderBy' => 'dateUpdated DESC, id DESC']);
        $conflicts = $salesService->detectConflicts();

        return $this->renderTemplate('commerce-sale-scheduler/dashboard/index', [
            'stats' => $stats,
            'recentSales' => $recentSales,
            'conflicts' => $conflicts,
            'title' => Craft::t('commerce-sale-scheduler', 'Sale Scheduler Dashboard'),
        ]);
    }
}
