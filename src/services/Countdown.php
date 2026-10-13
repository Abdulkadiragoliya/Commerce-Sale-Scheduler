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
        $heading = $options['heading'] ?? ($sale->countdownHeading ?: Craft::t('commerce-sale-scheduler', 'Sale Ends In'));
        $safeHeading = Html::encode($heading);
        $clockIcon = '<svg class="sale-countdown__icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
        $headerHtml = $safeHeading !== '' 
            ? "<div class=\"sale-countdown__header\">{$clockIcon}<span class=\"sale-countdown__heading\">{$safeHeading}</span></div>" 
            : '';
        $customClass = Html::encode($options['class'] ?? $sale->countdownCustomClass ?? '');

        $settings = Plugin::getInstance()->getSettings();
        $headingColor = $options['headingColor'] ?? $sale->countdownHeadingColor ?? $settings->defaultCountdownHeadingColor;
        $digitsColor = $options['digitsColor'] ?? $sale->countdownDigitsColor ?? $settings->defaultCountdownDigitsColor;

        $formatColor = static function(?string $color): ?string {
            if ($color === null) {
                return null;
            }
            $color = trim($color);
            if ($color === '') {
                return null;
            }
            if ($color[0] !== '#' && preg_match('/^[a-fA-F0-9]{3,8}$/', $color)) {
                return '#' . $color;
            }
            return $color;
        };

        $headingColor = $formatColor($headingColor);
        $digitsColor = $formatColor($digitsColor);

        $styleAttrs = [];
        if (!empty($headingColor)) {
            $safeHeadingColor = Html::encode($headingColor);
            $styleAttrs[] = "--sale-countdown-heading-color: {$safeHeadingColor};";
        }
        if (!empty($digitsColor)) {
            $safeDigitsColor = Html::encode($digitsColor);
            $styleAttrs[] = "--sale-countdown-digit-color: {$safeDigitsColor};";
        }
        $timerStyleAttr = !empty($styleAttrs) ? ' style="' . implode(' ', $styleAttrs) . '"' : '';

        $labelDays = Html::encode($settings->primaryCountdownLabelDays ?: 'Days');
        $labelHours = Html::encode($settings->primaryCountdownLabelHours ?: 'Hours');
        $labelMinutes = Html::encode($settings->primaryCountdownLabelMinutes ?: 'Mins');
        $labelSeconds = Html::encode($settings->primaryCountdownLabelSeconds ?: 'Secs');

        $endTimestamp = $sale->dateTo->getTimestamp();
        $endIso = $sale->dateTo->format(DateTime::ATOM);

        $remainingSecs = max(0, $endTimestamp - $now->getTimestamp());
        $initDays = (int)floor($remainingSecs / 86400);
        $initHours = (int)floor(($remainingSecs % 86400) / 3600);
        $initMins = (int)floor(($remainingSecs % 3600) / 60);
        $initSecs = (int)($remainingSecs % 60);

        $strDays = str_pad((string)$initDays, 2, '0', STR_PAD_LEFT);
        $strHours = str_pad((string)$initHours, 2, '0', STR_PAD_LEFT);
        $strMins = str_pad((string)$initMins, 2, '0', STR_PAD_LEFT);
        $strSecs = str_pad((string)$initSecs, 2, '0', STR_PAD_LEFT);

        return <<<HTML
<div class="sale-countdown-timer sale-countdown--{$style} {$customClass}"{$timerStyleAttr} data-sale-id="{$sale->id}" data-countdown-end="{$endTimestamp}" data-countdown-iso="{$endIso}" role="timer" aria-live="polite">
    {$headerHtml}
    <div class="sale-countdown__units">
        <div class="sale-countdown__unit sale-countdown__unit--days">
            <span class="sale-countdown__digit" data-unit="days">{$strDays}</span>
            <span class="sale-countdown__label">
                <span class="sale-countdown__label-full">{$labelDays}</span>
                <span class="sale-countdown__label-short">d</span>
            </span>
        </div>
        <span class="sale-countdown__separator">:</span>
        <div class="sale-countdown__unit sale-countdown__unit--hours">
            <span class="sale-countdown__digit" data-unit="hours">{$strHours}</span>
            <span class="sale-countdown__label">
                <span class="sale-countdown__label-full">{$labelHours}</span>
                <span class="sale-countdown__label-short">h</span>
            </span>
        </div>
        <span class="sale-countdown__separator">:</span>
        <div class="sale-countdown__unit sale-countdown__unit--minutes">
            <span class="sale-countdown__digit" data-unit="minutes">{$strMins}</span>
            <span class="sale-countdown__label">
                <span class="sale-countdown__label-full">{$labelMinutes}</span>
                <span class="sale-countdown__label-short">m</span>
            </span>
        </div>
        <span class="sale-countdown__separator">:</span>
        <div class="sale-countdown__unit sale-countdown__unit--seconds">
            <span class="sale-countdown__digit" data-unit="seconds">{$strSecs}</span>
            <span class="sale-countdown__label">
                <span class="sale-countdown__label-full">{$labelSeconds}</span>
                <span class="sale-countdown__label-short">s</span>
            </span>
        </div>
    </div>
</div>
HTML;
    }
}
