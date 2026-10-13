<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesalescheduler\variables;

use abdulkadiragoliya\commercesalescheduler\Plugin;
use abdulkadiragoliya\commercesalescheduler\web\twig\Extension;
use Twig\Markup;

/**
 * Sale Scheduler Craft Variable
 * Enables craft.saleScheduler.* syntax in Twig templates.
 */
class SaleSchedulerVariable
{
    private ?Extension $_extension = null;

    private function getExtension(): Extension
    {
        if ($this->_extension === null) {
            $this->_extension = new Extension();
        }
        return $this->_extension;
    }

    /**
     * Render promotional tag badge
     */
    public function tag($element, array $options = []): Markup
    {
        return $this->getExtension()->tag($element, $options);
    }

    /**
     * Render price component with strikethrough original and sale price
     */
    public function price($element, array $options = []): Markup
    {
        return $this->getExtension()->price($element, $options);
    }

    /**
     * Render countdown timer
     */
    public function countdown($element, array $options = []): Markup
    {
        return $this->getExtension()->countdown($element, $options);
    }

    /**
     * Render full cohesive sale block
     */
    public function saleBlock($element, array $options = []): Markup
    {
        return $this->getExtension()->saleBlock($element, $options);
    }

    /**
     * Get winning sale model
     */
    public function getSale($element)
    {
        return $this->getExtension()->getSale($element);
    }

    /**
     * Get sale calculation details
     */
    public function getSaleInfo($element): array
    {
        return $this->getExtension()->getSaleInfo($element);
    }

    /**
     * Output CSS link tag
     */
    public function css(): Markup
    {
        return $this->getExtension()->css();
    }

    /**
     * Output JS script tag
     */
    public function js(): Markup
    {
        return $this->getExtension()->js();
    }
}
