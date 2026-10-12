<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\services;

use Craft;
use craft\base\Component;
use craft\helpers\Html;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;
use DateTime;

/**
 * Countdown Timer Service
 */
class Countdown extends Component
{
    /**
     * Render the countdown timer for a product or variant
     */
    public function renderCountdown($element, array $options = []): string
    {
        $sale = Plugin::getInstance()->eligibility->getWinningSale($element);
        if (!$sale || !$sale->countdownEnabled || !$sale->dateTo) {
            return '';
        }

        $context = $options['context'] ?? 'detail';
        if ($context === 'listing' && !$sale->countdownShowListing) {
            return '';
        }
        if ($context === 'detail' && !$sale->countdownShowDetail) {
            return '';
        }

        $now = new DateTime('now');
        if ($now >= $sale->dateTo) {
            return '';
        }

        $style = Html::encode($options['style'] ?? $sale->countdownStyle ?? 'compact');
        $heading = Html::encode($options['heading'] ?? $sale->countdownHeading ?? Craft::t('commerce-sale-scheduler', 'Sale Ends In'));
        $customClass = Html::encode($options['class'] ?? $sale->countdownCustomClass ?? '');

        $settings = Plugin::getInstance()->getSettings();
        $labelDays = Html::encode($settings->primaryCountdownLabelDays);
        $labelHours = Html::encode($settings->primaryCountdownLabelHours);
        $labelMinutes = Html::encode($settings->primaryCountdownLabelMinutes);
        $labelSeconds = Html::encode($settings->primaryCountdownLabelSeconds);

        $endTimestamp = $sale->dateTo->getTimestamp();
        $endIso = $sale->dateTo->format(DateTime::ATOM);

        return <<<HTML
<div class="sale-countdown-timer sale-countdown--{$style} {$customClass}" data-sale-id="{$sale->id}" data-countdown-end="{$endTimestamp}" data-countdown-iso="{$endIso}" role="timer" aria-live="polite">
    <div class="sale-countdown__header">
        <span class="sale-countdown__heading">{$heading}</span>
    </div>
    <div class="sale-countdown__units">
        <div class="sale-countdown__unit sale-countdown__unit--days">
            <span class="sale-countdown__digit" data-unit="days">00</span>
            <span class="sale-countdown__label">{$labelDays}</span>
        </div>
        <span class="sale-countdown__separator">:</span>
        <div class="sale-countdown__unit sale-countdown__unit--hours">
            <span class="sale-countdown__digit" data-unit="hours">00</span>
            <span class="sale-countdown__label">{$labelHours}</span>
        </div>
        <span class="sale-countdown__separator">:</span>
        <div class="sale-countdown__unit sale-countdown__unit--minutes">
            <span class="sale-countdown__digit" data-unit="minutes">00</span>
            <span class="sale-countdown__label">{$labelMinutes}</span>
        </div>
        <span class="sale-countdown__separator">:</span>
        <div class="sale-countdown__unit sale-countdown__unit--seconds">
            <span class="sale-countdown__digit" data-unit="seconds">00</span>
            <span class="sale-countdown__label">{$labelSeconds}</span>
        </div>
    </div>
</div>
HTML;
    }
}
