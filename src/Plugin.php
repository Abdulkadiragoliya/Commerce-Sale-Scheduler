<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\commerce\events\LineItemEvent;
use craft\commerce\services\LineItems;
use craft\commerce\services\OrderAdjustments;
use craft\elements\Category;
use craft\elements\Product;
use craft\events\ElementEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use abdulkadiragoliya\commercesalescheduler\adjusters\SaleAdjuster;
use abdulkadiragoliya\commercesalescheduler\models\Settings;
use abdulkadiragoliya\commercesalescheduler\services\CacheService;
use abdulkadiragoliya\commercesalescheduler\services\Countdown;
use abdulkadiragoliya\commercesalescheduler\services\DiscountEngine;
use abdulkadiragoliya\commercesalescheduler\services\Eligibility;
use abdulkadiragoliya\commercesalescheduler\services\Sales;
use abdulkadiragoliya\commercesalescheduler\services\TagManager;
use abdulkadiragoliya\commercesalescheduler\variables\SaleSchedulerVariable;
use abdulkadiragoliya\commercesalescheduler\web\twig\Extension;
use yii\base\Event;

/**
 * Commerce Sale Scheduler Plugin
 *
 * @property Sales $sales
 * @property DiscountEngine $discounts
 * @property Eligibility $eligibility
 * @property TagManager $tagManager
 * @property Countdown $countdown
 * @property CacheService $cache
 */
class Plugin extends BasePlugin
{
    public static Plugin $plugin;

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Register components
        $this->setComponents([
            'sales' => Sales::class,
            'discounts' => DiscountEngine::class,
            'eligibility' => Eligibility::class,
            'tagManager' => TagManager::class,
            'countdown' => Countdown::class,
            'cache' => CacheService::class,
        ]);

        // Register Twig Extension
        Craft::$app->getView()->registerTwigExtension(new Extension());

        // Register craft.saleScheduler Twig variable
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('saleScheduler', SaleSchedulerVariable::class);
            }
        );

        // Register CP URL rules
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['sale-scheduler'] = 'commerce-sale-scheduler/dashboard/index';
                $event->rules['sale-scheduler/dashboard'] = 'commerce-sale-scheduler/dashboard/index';
                $event->rules['sale-scheduler/sales'] = 'commerce-sale-scheduler/sales/index';
                $event->rules['sale-scheduler/sales/new'] = 'commerce-sale-scheduler/sales/edit';
                $event->rules['sale-scheduler/sales/create'] = 'commerce-sale-scheduler/sales/edit';
                $event->rules['sale-scheduler/sales/<id:\d+>'] = 'commerce-sale-scheduler/sales/edit';
                $event->rules['sale-scheduler/sales/edit'] = 'commerce-sale-scheduler/sales/edit';
                $event->rules['sale-scheduler/settings'] = 'commerce-sale-scheduler/settings/index';

                // Also register commerce-sale-scheduler aliases
                $event->rules['commerce-sale-scheduler'] = 'commerce-sale-scheduler/dashboard/index';
                $event->rules['commerce-sale-scheduler/dashboard'] = 'commerce-sale-scheduler/dashboard/index';
                $event->rules['commerce-sale-scheduler/sales'] = 'commerce-sale-scheduler/sales/index';
                $event->rules['commerce-sale-scheduler/sales/new'] = 'commerce-sale-scheduler/sales/edit';
                $event->rules['commerce-sale-scheduler/sales/<id:\d+>'] = 'commerce-sale-scheduler/sales/edit';
                $event->rules['commerce-sale-scheduler/settings'] = 'commerce-sale-scheduler/settings/index';
            }
        );

        // Register User Permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[Craft::t('commerce-sale-scheduler', 'Sale Scheduler')] = [
                    'commerce-sale-scheduler:manage' => [
                        'label' => Craft::t('commerce-sale-scheduler', 'Manage scheduled sales and settings'),
                    ],
                ];
            }
        );

        // Register Commerce Order Adjuster
        Event::on(
            OrderAdjustments::class,
            OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SaleAdjuster::class;
            }
        );

        // Hook LineItem populate event to apply sale pricing
        Event::on(
            LineItems::class,
            LineItems::EVENT_POPULATE_LINE_ITEM,
            function (LineItemEvent $event) {
                $lineItem = $event->lineItem;
                $purchasable = $lineItem->getPurchasable();
                if ($purchasable) {
                    $sale = $this->eligibility->getWinningSale($purchasable);
                    if ($sale) {
                        $calc = $this->discounts->calculate((float)$lineItem->price, $sale);
                        if ($calc->isSaleApplied) {
                            if (method_exists($lineItem, 'setPromotionalPrice')) {
                                $lineItem->setPromotionalPrice($calc->salePrice);
                            } elseif (method_exists($lineItem, 'setSalePrice')) {
                                $lineItem->setSalePrice($calc->salePrice);
                            }
                            $snapshot = $lineItem->getSnapshot();
                            $snapshot['salescheduler_sale_id'] = $sale->id;
                            $snapshot['salescheduler_sale_name'] = $sale->name;
                            $lineItem->setSnapshot($snapshot);
                        }
                    }
                }
            }
        );

        // Invalidate cache when products or categories change
        $cacheInvalidator = function (ElementEvent $event) {
            if ($event->element instanceof Product || $event->element instanceof Category) {
                $this->cache->invalidateAll();
            }
        };
        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, $cacheInvalidator);
        Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, $cacheInvalidator);
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        if ($item) {
            $item['label'] = Craft::t('commerce-sale-scheduler', 'Sale Scheduler');
            $item['url'] = 'sale-scheduler';
            $item['fontIcon'] = 'calendar';
            $item['subnav'] = [
                'dashboard' => ['label' => Craft::t('commerce-sale-scheduler', 'Dashboard'), 'url' => 'sale-scheduler/dashboard'],
                'sales' => ['label' => Craft::t('commerce-sale-scheduler', 'Sales'), 'url' => 'sale-scheduler/sales'],
                'settings' => ['label' => Craft::t('commerce-sale-scheduler', 'Settings'), 'url' => 'sale-scheduler/settings'],
            ];
        }
        return $item;
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect('sale-scheduler/settings');
    }
}
