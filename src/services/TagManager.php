<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\services;

use Craft;
use craft\base\Component;
use craft\helpers\Html;
use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\models\Sale;

/**
 * Tag Image Manager Service
 */
class TagManager extends Component
{
    /**
     * Render the promotional tag for a product or variant
     */
    public function renderTag($element, array $options = []): string
    {
        $sale = Plugin::getInstance()->eligibility->getWinningSale($element);
        if (!$sale || !$sale->tagEnabled) {
            return '';
        }

        $context = $options['context'] ?? 'detail';
        $position = ($context === 'listing') ? $sale->tagPositionListing : $sale->tagPositionDetail;
        $width = $options['width'] ?? $sale->tagWidth ?? '80px';
        $customClass = Html::encode($options['class'] ?? $sale->tagCustomClass ?? '');

        $asset = $sale->getTagAsset();

        if ($asset) {
            $url = $asset->getUrl();
            $alt = Html::encode($sale->tagText ?: ($asset->title ?: $sale->name));
            $widthStyle = $width ? "width: {$width}; max-width: 100%;" : '';

            return <<<HTML
<div class="sale-tag-badge sale-tag-badge--{$position} {$customClass}" data-sale-id="{$sale->id}" style="{$widthStyle}">
    <img src="{$url}" alt="{$alt}" class="sale-tag-badge__image" loading="lazy" />
</div>
HTML;
        }

        // Text badge fallback
        $text = $sale->tagText ?: $sale->badgeText ?: $sale->getFormattedDiscount() . ' OFF';
        $safeText = Html::encode($text);

        return <<<HTML
<div class="sale-tag-badge sale-tag-badge--text sale-tag-badge--{$position} {$customClass}" data-sale-id="{$sale->id}">
    <span class="sale-tag-badge__label">{$safeText}</span>
</div>
HTML;
    }
}
