# Commerce Sale Scheduler — Developer Guide

This guide covers the Twig API, styling, JavaScript lifecycle, and customization for frontend and backend engineers.

---

## 1. Twig API Reference

The plugin exposes the `craft.saleScheduler` global object in all Twig templates.

### `craft.saleScheduler.tag(element, options = {})`
Renders the promotional tag badge (asset image or fallback text badge).

- **Arguments**:
  - `element` (`Product` | `Variant` | `int`): The Commerce product or variant.
  - `options` (`array`):
    - `context` (`string`): `'listing'` or `'detail'` (default: `'detail'`). Determines which configured position to apply.
    - `class` (`string`): Additional CSS class to append to the container.
    - `width` (`string`): Override tag width (e.g., `'90px'`).
- **Returns**: `\Twig\Markup` (HTML string). Empty string if no active sale or tag disabled.

```twig
{{ craft.saleScheduler.tag(product, { context: 'listing' }) }}
```

---

### `craft.saleScheduler.price(element, options = {})`
Renders the strikethrough original price, discounted sale price, optional savings note, and promotional badge.

- **Arguments**:
  - `element` (`Product` | `Variant`): The Commerce product or variant.
  - `options` (`array`):
    - `showOriginal` (`bool`): Override sale setting for showing original strikethrough price.
    - `showSavings` (`bool`): Override sale setting for showing savings amount.
    - `showBadge` (`bool`): Override sale setting for showing promotional badge.
    - `class` (`string`): Additional CSS class.
- **Returns**: `\Twig\Markup` (HTML string). If no sale applies, outputs the standard formatted price.

```twig
{{ craft.saleScheduler.price(product.defaultVariant) }}
```

---

### `craft.saleScheduler.countdown(element, options = {})`
Renders the live countdown timer.

- **Arguments**:
  - `element` (`Product` | `Variant`): The Commerce product or variant.
  - `options` (`array`):
    - `context` (`string`): `'listing'` or `'detail'` (default: `'detail'`).
    - `style` (`string`): `'compact'`, `'boxed'`, or `'minimal'`.
    - `heading` (`string`): Override countdown heading text.
    - `class` (`string`): Custom CSS class.
- **Returns**: `\Twig\Markup` (HTML string). Empty string if no end date, expired, or countdown disabled.

```twig
{{ craft.saleScheduler.countdown(product.defaultVariant, { style: 'boxed' }) }}
```

---

### `craft.saleScheduler.saleBlock(element, options = {})`
Renders a cohesive container including the tag badge, price display, and countdown timer.

```twig
{{ craft.saleScheduler.saleBlock(product) }}
```

---

### `craft.saleScheduler.getSale(element)`
Returns the winning `Sale` model instance or `null`.

```twig
{% set winningSale = craft.saleScheduler.getSale(product) %}
{% if winningSale %}
    <p>Special Promotion: {{ winningSale.name }}</p>
{% endif %}
```

---

### `craft.saleScheduler.getSaleInfo(element)`
Returns an associative array with calculated discount pricing details:

```twig
{% set info = craft.saleScheduler.getSaleInfo(product.defaultVariant) %}
{% if info.hasSale %}
    <span>Sale Price: {{ info.formattedSalePrice }}</span>
    <span>Regular Price: {{ info.formattedOriginalPrice }}</span>
    <span>You Save: {{ info.formattedSavingsAmount }} ({{ info.formattedSavingsPercent }})</span>
{% endif %}
```

---

### `craft.saleScheduler.css()` & `craft.saleScheduler.js()`
Helper functions that publish and output links to the bundled CSS and lightweight JavaScript.

```twig
{# Place in layout <head> #}
{{ craft.saleScheduler.css() }}

{# Place before closing </body> #}
{{ craft.saleScheduler.js() }}
```

---

## 2. Storefront Integration Examples

### Product Listing Card (`_includes/product-card.twig`)

```twig
<div class="product-card has-sale-tag">
    <div class="product-card__image-container">
        {# Tag badge positioned over the image #}
        {{ craft.saleScheduler.tag(product, { context: 'listing' }) }}

        <a href="{{ product.url }}">
            <img src="{{ product.productImage.one().url ?? '' }}" alt="{{ product.title }}">
        </a>
    </div>

    <div class="product-card__details">
        <h3 class="product-card__title"><a href="{{ product.url }}">{{ product.title }}</a></h3>

        {# Price display #}
        {{ craft.saleScheduler.price(product) }}
    </div>
</div>
```

### Product Detail Page (`shop/products/_entry.twig`)

```twig
{% extends "_layouts/site" %}

{% block content %}
    <div class="product-page">
        <div class="product-page__gallery has-sale-tag">
            {{ craft.saleScheduler.tag(product, { context: 'detail' }) }}
            <img id="main-product-image" src="{{ product.productImage.one().url ?? '' }}" alt="{{ product.title }}">
        </div>

        <div class="product-page__info">
            <h1>{{ product.title }}</h1>

            <div id="price-container">
                {{ craft.saleScheduler.price(product.defaultVariant) }}
            </div>

            <div id="countdown-container">
                {{ craft.saleScheduler.countdown(product.defaultVariant) }}
            </div>

            <form method="post" action="">
                {{ csrfInput() }}
                {{ actionInput('commerce/cart/update-cart') }}
                <input type="hidden" name="purchasableId" value="{{ product.defaultVariant.id }}">
                <input type="number" name="qty" value="1" min="1">
                <button type="submit" class="btn btn-primary">Add to Cart</button>
            </form>
        </div>
    </div>
{% endblock %}
```

---

## 3. JavaScript Events

When a countdown timer reaches 0 on the client, it automatically hides the element and dispatches a custom DOM event:

```javascript
document.addEventListener('salescheduler:expired', function(e) {
    console.log('Sale expired:', e.detail.saleId);
    // Optionally trigger an AJAX cart refresh or notice
});
```

---

## 4. Custom Styling & Overrides

You can easily style or override components using standard CSS:

```css
/* Custom tag badge styling */
.sale-tag-badge--top-right {
    top: 16px;
    right: 16px;
}

/* Custom sale price color */
.sale-price-display--on-sale .sale-price-display__current {
    color: #e11d48;
    font-size: 1.5rem;
}

/* Custom countdown background */
.sale-countdown-timer {
    background: #ffffff;
    border-color: #f43f5e;
}
```
