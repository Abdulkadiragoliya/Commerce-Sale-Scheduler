<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\adjusters;

use Craft;
use craft\base\Component;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use DateTime;

/**
 * Sale Adjuster for Craft Commerce Orders & Carts
 *
 * Ensures cart and checkout line items strictly reflect active scheduled sales,
 * and immediately reverts line item pricing if a scheduled sale has expired.
 */
class SaleAdjuster extends Component implements AdjusterInterface
{
    /**
     * @inheritdoc
     */
    public function adjust(Order $order): array
    {
        $evaluationDate = $order->dateOrdered ?? new DateTime('now');
        $siteId = (int)$order->siteId;

        foreach ($order->getLineItems() as $lineItem) {
            $purchasable = $lineItem->getPurchasable();
            if (!$purchasable) {
                continue;
            }

            $sale = Plugin::getInstance()->eligibility->getWinningSale($purchasable, $evaluationDate, $siteId);

            if ($sale) {
                $calculation = Plugin::getInstance()->discounts->calculate((float)$lineItem->price, $sale);
                if ($calculation->isSaleApplied) {
                    if (method_exists($lineItem, 'setPromotionalPrice')) {
                        $lineItem->setPromotionalPrice($calculation->salePrice);
                    } elseif (method_exists($lineItem, 'setSalePrice')) {
                        $lineItem->setSalePrice($calculation->salePrice);
                    }
                } else {
                    if (method_exists($lineItem, 'setPromotionalPrice')) {
                        $lineItem->setPromotionalPrice(null);
                    } elseif (method_exists($lineItem, 'setSalePrice')) {
                        $lineItem->setSalePrice($lineItem->price);
                    }
                }
            } else {
                // If there was a scheduled promotional price and no active sale remains, revert it
                $snapshot = $lineItem->getSnapshot();
                if (!empty($snapshot['salescheduler_sale_id'])) {
                    if (method_exists($lineItem, 'setPromotionalPrice')) {
                        $lineItem->setPromotionalPrice(null);
                    } elseif (method_exists($lineItem, 'setSalePrice')) {
                        $lineItem->setSalePrice($lineItem->price);
                    }
                }
            }
        }

        return [];
    }
}
