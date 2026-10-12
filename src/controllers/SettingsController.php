<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\controllers;

use Craft;
use craft\web\Controller;
use abdulkadiragoliya\commercesalescheduler\Plugin;
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
            $this->setFailNotification(Craft::t('commerce-sale-scheduler', 'Could not save settings.'));
            return $this->renderTemplate('commerce-sale-scheduler/settings/index', [
                'settings' => Plugin::getInstance()->getSettings(),
            ]);
        }

        $this->setSuccessNotification(Craft::t('commerce-sale-scheduler', 'Settings saved successfully.'));
        return $this->redirectToPostedUrl(null, 'sale-scheduler/settings');
    }
}
