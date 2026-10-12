<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\controllers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Control Panel Sales Controller
 */
class SalesController extends Controller
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
     * Sale listing
     */
    public function actionIndex(): Response
    {
        $salesService = Plugin::getInstance()->sales;
        $search = (string)$this->request->getQueryParam('search', '');
        $status = (string)$this->request->getQueryParam('status', 'all');

        $criteria = [
            'search' => $search,
            'status' => $status,
        ];

        $sales = $salesService->getAllSales($criteria);

        return $this->renderTemplate('commerce-sale-scheduler/sales/index', [
            'sales' => $sales,
            'search' => $search,
            'status' => $status,
            'title' => Craft::t('commerce-sale-scheduler', 'Scheduled Sales'),
        ]);
    }

    /**
     * Create or edit a sale
     */
    public function actionEdit(?int $id = null, ?Sale $sale = null): Response
    {
        $salesService = Plugin::getInstance()->sales;

        if ($sale === null) {
            if ($id !== null) {
                $sale = $salesService->getSaleById($id);
                if (!$sale) {
                    throw new NotFoundHttpException(Craft::t('commerce-sale-scheduler', 'Sale not found.'));
                }
            } else {
                $sale = new Sale();
                $settings = Plugin::getInstance()->getSettings();
                $sale->countdownStyle = $settings->defaultCountdownStyle;
                $sale->tagPositionListing = $settings->defaultTagPositionListing;
                $sale->tagPositionDetail = $settings->defaultTagPositionDetail;
                $sale->tagWidth = $settings->defaultTagWidth;
                $sale->tagEnabled = $settings->defaultTagEnabled;
                $sale->countdownEnabled = $settings->defaultCountdownEnabled;
                $sale->countdownShowListing = $settings->defaultCountdownShowListing;
                $sale->countdownShowDetail = $settings->defaultCountdownShowDetail;
                $sale->priceShowOriginal = $settings->defaultPriceShowOriginal;
                $sale->priceShowSavings = $settings->defaultPriceShowSavings;
                $sale->priceShowBadge = $settings->defaultPriceShowBadge;
            }
        }

        $affectedCounts = Plugin::getInstance()->eligibility->getAffectedCounts($sale);

        return $this->renderTemplate('commerce-sale-scheduler/sales/_edit', [
            'sale' => $sale,
            'isNew' => !$sale->id,
            'affectedCounts' => $affectedCounts,
            'title' => $sale->id 
                ? Craft::t('commerce-sale-scheduler', 'Edit Sale: {name}', ['name' => $sale->name]) 
                : Craft::t('commerce-sale-scheduler', 'Create New Sale'),
        ]);
    }

    /**
     * Save sale
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = $this->request;
        $salesService = Plugin::getInstance()->sales;

        $id = $request->getBodyParam('id');
        if ($id) {
            $sale = $salesService->getSaleById((int)$id);
            if (!$sale) {
                throw new NotFoundHttpException(Craft::t('commerce-sale-scheduler', 'Sale not found.'));
            }
        } else {
            $sale = new Sale();
        }

        $sale->name = (string)$request->getBodyParam('name', '');
        $sale->description = $request->getBodyParam('description');
        $sale->enabled = (bool)$request->getBodyParam('enabled', true);
        $sale->priority = (int)$request->getBodyParam('priority', 0);
        $sale->discountType = (string)$request->getBodyParam('discountType', Sale::TYPE_PERCENTAGE);
        $sale->discountValue = (float)$request->getBodyParam('discountValue', 0.0);

        $dateFromParam = $request->getBodyParam('dateFrom');
        $dateToParam = $request->getBodyParam('dateTo');
        $sale->dateFrom = $dateFromParam ? DateTimeHelper::toDateTime($dateFromParam) : null;
        $sale->dateTo = $dateToParam ? DateTimeHelper::toDateTime($dateToParam) : null;

        $sale->applyToAllVariants = (bool)$request->getBodyParam('applyToAllVariants', true);
        $sale->allSites = (bool)$request->getBodyParam('allSites', true);

        // Relations
        $sale->productIds = array_filter(array_map('intval', (array)$request->getBodyParam('productIds', [])));
        $sale->variantIds = array_filter(array_map('intval', (array)$request->getBodyParam('variantIds', [])));
        $sale->categoryIds = array_filter(array_map('intval', (array)$request->getBodyParam('categoryIds', [])));
        $sale->exclusionIds = array_filter(array_map('intval', (array)$request->getBodyParam('exclusionIds', [])));
        $sale->siteIds = array_filter(array_map('intval', (array)$request->getBodyParam('siteIds', [])));

        // Tag Settings
        $tagAssetIds = (array)$request->getBodyParam('tagAssetId', []);
        $sale->tagAssetId = !empty($tagAssetIds) ? (int)reset($tagAssetIds) : null;
        $sale->tagText = $request->getBodyParam('tagText');
        $sale->tagEnabled = (bool)$request->getBodyParam('tagEnabled', true);
        $sale->tagPositionListing = (string)$request->getBodyParam('tagPositionListing', 'top-right');
        $sale->tagPositionDetail = (string)$request->getBodyParam('tagPositionDetail', 'top-right');
        $sale->tagWidth = (string)$request->getBodyParam('tagWidth', '80px');
        $sale->tagCustomClass = $request->getBodyParam('tagCustomClass');

        // Countdown Settings
        $sale->countdownEnabled = (bool)$request->getBodyParam('countdownEnabled', true);
        $sale->countdownStyle = (string)$request->getBodyParam('countdownStyle', 'compact');
        $sale->countdownHeading = $request->getBodyParam('countdownHeading');
        $sale->countdownShowListing = (bool)$request->getBodyParam('countdownShowListing', false);
        $sale->countdownShowDetail = (bool)$request->getBodyParam('countdownShowDetail', true);
        $sale->countdownCustomClass = $request->getBodyParam('countdownCustomClass');

        // Price Display Settings
        $sale->priceShowOriginal = (bool)$request->getBodyParam('priceShowOriginal', true);
        $sale->priceShowSavings = (bool)$request->getBodyParam('priceShowSavings', true);
        $sale->priceShowBadge = (bool)$request->getBodyParam('priceShowBadge', true);
        $sale->badgeText = $request->getBodyParam('badgeText');

        if (!$salesService->saveSale($sale)) {
            $this->setFailNotification(Craft::t('commerce-sale-scheduler', 'Could not save sale.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'sale' => $sale,
            ]);
            return $this->actionEdit($sale->id, $sale);
        }

        $this->setSuccessNotification(Craft::t('commerce-sale-scheduler', 'Sale saved successfully.'));

        return $this->redirectToPostedUrl($sale, 'sale-scheduler/sales');
    }

    /**
     * Duplicate sale
     */
    public function actionDuplicate(): Response
    {
        $this->requirePostRequest();
        $id = (int)$this->request->getRequiredBodyParam('id');

        $duplicate = Plugin::getInstance()->sales->duplicateSale($id);

        if ($duplicate) {
            $this->setSuccessNotification(Craft::t('commerce-sale-scheduler', 'Sale duplicated as draft.'));
            return $this->redirect('sale-scheduler/sales/edit?id=' . $duplicate->id);
        }

        $this->setFailNotification(Craft::t('commerce-sale-scheduler', 'Could not duplicate sale.'));
        return $this->redirect('sale-scheduler/sales');
    }

    /**
     * Delete sale
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $id = (int)$this->request->getRequiredBodyParam('id');

        if (Plugin::getInstance()->sales->deleteSaleById($id)) {
            $this->setSuccessNotification(Craft::t('commerce-sale-scheduler', 'Sale deleted successfully.'));
        } else {
            $this->setFailNotification(Craft::t('commerce-sale-scheduler', 'Could not delete sale.'));
        }

        return $this->redirect('sale-scheduler/sales');
    }

    /**
     * Toggle status via AJAX or POST
     */
    public function actionToggleStatus(): Response
    {
        $this->requirePostRequest();
        $id = (int)$this->request->getRequiredBodyParam('id');

        $success = Plugin::getInstance()->sales->toggleStatus($id);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => $success]);
        }

        if ($success) {
            $this->setSuccessNotification(Craft::t('commerce-sale-scheduler', 'Sale status updated.'));
        } else {
            $this->setFailNotification(Craft::t('commerce-sale-scheduler', 'Could not update status.'));
        }

        return $this->redirect('sale-scheduler/sales');
    }

    /**
     * Bulk actions
     */
    public function actionBulkAction(): Response
    {
        $this->requirePostRequest();
        $action = (string)$this->request->getRequiredBodyParam('bulkAction');
        $ids = (array)$this->request->getBodyParam('ids', []);

        if (empty($ids)) {
            $this->setFailNotification(Craft::t('commerce-sale-scheduler', 'No sales selected.'));
            return $this->redirect('sale-scheduler/sales');
        }

        $count = Plugin::getInstance()->sales->bulkAction($action, $ids);
        $this->setSuccessNotification(Craft::t('commerce-sale-scheduler', '{count} sale(s) updated.', ['count' => $count]));

        return $this->redirect('sale-scheduler/sales');
    }

    /**
     * Preview calculation endpoint
     */
    public function actionPreview(): Response
    {
        $originalPrice = (float)$this->request->getQueryParam('price', 100.0);
        $type = (string)$this->request->getQueryParam('type', Sale::TYPE_PERCENTAGE);
        $value = (float)$this->request->getQueryParam('value', 20.0);

        $tempSale = new Sale();
        $tempSale->discountType = $type;
        $tempSale->discountValue = $value;

        $calc = Plugin::getInstance()->discounts->calculate($originalPrice, $tempSale);

        return $this->asJson([
            'originalPrice' => $calc->originalPrice,
            'salePrice' => $calc->salePrice,
            'savingsAmount' => $calc->savingsAmount,
            'savingsPercent' => $calc->savingsPercent,
            'formattedOriginal' => $calc->getFormattedOriginalPrice(),
            'formattedSale' => $calc->getFormattedSalePrice(),
            'formattedSavings' => $calc->getFormattedSavingsAmount(),
        ]);
    }
}
