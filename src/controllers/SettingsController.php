<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\controllers;

use Craft;
use craft\web\Controller;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\web\assets\cp\CpAsset;
use yii\web\Response;

/**
 * Control Panel Settings Controller
 */
class SettingsController extends Controller
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
     * Settings page
     */
    public function actionIndex(): Response
    {
        $settings = Plugin::getInstance()->getSettings();

        return $this->renderTemplate('commerce-sale-scheduler/settings/index', [
            'settings' => $settings,
            'title' => Craft::t('commerce-sale-scheduler', 'Sale Scheduler Settings'),
        ]);
    }

    /**
     * Save settings
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $params = (array)$this->request->getBodyParam('settings', []);

        if (!Craft::$app->getPlugins()->savePluginSettings(Plugin::getInstance(), $params)) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sale-scheduler', 'Could not save settings.'));
            return $this->renderTemplate('commerce-sale-scheduler/settings/index', [
                'settings' => Plugin::getInstance()->getSettings(),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('commerce-sale-scheduler', 'Settings saved successfully.'));
        return $this->redirectToPostedUrl(null, 'sale-scheduler/settings');
    }
}
