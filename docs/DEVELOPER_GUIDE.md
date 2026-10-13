# Commerce Sale Scheduler — Developer & Integration Guide

This guide provides frontend and backend developers with complete instructions on how to integrate **Commerce Sale Scheduler** into any Craft CMS 5 & Craft Commerce 5 storefront.

---

## 1. Quick Integration Overview (What Code Goes Where)

Integrating this plugin requires **zero architectural redesign** and **zero custom cart calculations**. You only need to drop in Twig helper functions into three key locations:

```
templates/
├── _layout.twig           <-- 1. Add CSS in <head> and JS before </body>
└── shop/
    ├── index.twig         <-- 2. Add Tag Badge + Sale Price to product listing cards
    └── _product.twig      <-- 3. Add Tag Badge + Sale Price + Live Countdown to detail page
```

---

## 2. Step-by-Step Template Integration

### Step 1: Base Layout (`templates/_layout.twig`)

In your root layout template, include the plugin's CSS in the `<head>` and the lightweight timer JavaScript before the closing `</body>` tag.

```twig
{# templates/_layout.twig #}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{% block title %}Atelier Studio{% endblock %}</title>

    {# 1. Output plugin CSS stylesheet #}
    {{ saleSchedulerCss() }}
    
    {# Alternatively: {{ craft.saleScheduler.css() }} #}
</head>
<body>

    {% block content %}
    {% endblock %}

    {# 2. Output lightweight countdown JavaScript (vanilla JS, no dependencies) #}
    {# IMPORTANT: Place outside child blocks so sub-templates don't suppress it #}
    {{ saleSchedulerJs() }}
    
    {# Alternatively: {{ craft.saleScheduler.js() }} #}
</body>
</html>
```

---

### Step 2: Product Listing / Catalog Cards (`templates/shop/index.twig`)

On your catalog or product listing grid, you want to show:
1. The **Promotional Tag Badge** (e.g. image badge or "35% OFF" text pill) placed over the product thumbnail.
2. The **Sale Price Component** showing the discounted price and strikethrough original price.
3. *(Optional)* A listing-level countdown timer.

```twig
{# templates/shop/index.twig or templates/_includes/product-card.twig #}
<div class="product-grid">
  {% for product in products %}
    {% set defaultVariant = product.defaultVariant %}

    <article class="product-card">
      {# Wrap the thumbnail container with class "has-sale-tag" for relative badge positioning #}
      <div class="product-card__image-container has-sale-tag" style="position: relative;">
        
        {# A. PROMOTIONAL TAG BADGE (Listing context) #}
        {{ saleSchedulerTag(product, { context: 'listing' }) }}

        <a href="{{ product.url }}">
          <img src="{{ product.productImages.one().getUrl() ?? '' }}" alt="{{ product.title }}" loading="lazy">
        </a>
      </div>

      <div class="product-card__content">
        <h3 class="product-card__title">
          <a href="{{ product.url }}">{{ product.title }}</a>
        </h3>

        {# B. SALE PRICE DISPLAY #}
        {# Outputs: <span class="current">$55.25</span> <del>$85.00</del> <span class="savings">Save $29.75 (35%)</span> #}
        {{ saleSchedulerPrice(defaultVariant) }}

        {# C. (OPTIONAL) LISTING COUNTDOWN TIMER #}
        {# Respects the 'Show on Product Listing Cards' toggle in the sale settings #}
        {{ saleSchedulerCountdown(defaultVariant, { context: 'listing' }) }}
      </div>
    </article>
  {% endfor %}
</div>
```

---

### Step 3: Product Detail Page (`templates/shop/_product.twig`)

On the single product detail page, integrate the components into the main gallery and purchasing sections:

```twig
{# templates/shop/_product.twig #}
{% extends "_layout.twig" %}

{% block content %}
  {% set defaultVariant = product.defaultVariant %}
  {% set mainImage = product.productImages.one() %}

  <div class="product-page-layout">
    
    <!-- 1. Product Image Gallery -->
    <div class="product-gallery">
      <div class="gallery-main-image-wrap has-sale-tag" style="position: relative;">
        
        {# PROMOTIONAL TAG BADGE (Detail context) #}
        {# Positions badge based on the sale's 'Position on Product Detail Page' setting #}
        {{ saleSchedulerTag(product, { context: 'detail' }) }}

        <img src="{{ mainImage ? mainImage.getUrl() : '' }}" alt="{{ product.title }}">
      </div>
    </div>

    <!-- 2. Product Purchasing Panel -->
    <div class="product-details">
      <h1 class="product-title">{{ product.title }}</h1>

      {# STRIKETHROUGH PRICE + SINGLE SAVINGS PILL #}
      <div class="product-price-section">
        {{ saleSchedulerPrice(defaultVariant) }}
      </div>

      {# LIVE COUNTDOWN TIMER WIDGET #}
      {# Renders live days, hours, minutes, and seconds remaining #}
      {{ saleSchedulerCountdown(defaultVariant, { context: 'detail' }) }}

      {# STANDARD COMMERCE ADD-TO-BAG FORM (ZERO CHANGES NEEDED) #}
      <form method="post" action="" class="add-to-cart-form">
        {{ csrfInput() }}
        {{ actionInput('commerce/cart/update-cart') }}
        <input type="hidden" name="purchasableId" value="{{ defaultVariant.id }}">
        
        <div class="qty-group">
          <label for="qty">Quantity</label>
          <input type="number" id="qty" name="qty" value="1" min="1">
        </div>

        <button type="submit" class="btn btn-primary">Add to Bag</button>
      </form>
    </div>

  </div>
{% endblock %}
```

---

### Step 4: Cart and Checkout (`templates/shop/cart.twig`)

**Zero custom code required!**

When a customer adds a scheduled sale item to their shopping cart:
1. The plugin's `LineItems::EVENT_POPULATE_LINE_ITEM` hook intercepts the line item and sets its promotional price to the active sale discount.
2. The line item snapshot preserves the promotional sale metadata (`salescheduler_sale_id`, `salescheduler_sale_name`).
3. The plugin's native Commerce `SaleAdjuster` dynamically validates the sale on every cart and checkout refresh. If the sale period expires while the item is in the user's cart, the price safely reverts to the standard price before order completion.

---

## 3. Twig Helper API Reference

Both global function syntax and `craft.saleScheduler.*` syntax are supported identically.

### `saleSchedulerTag(element, options = [])`
*or `craft.saleScheduler.tag(element, options)`*

Renders the asset image badge or fallback text badge.

- **Parameters**:
  - `element` (`Product` | `Variant`): Target product or variant.
  - `options` (`array`):
    - `context` (`string`, default: `'detail'`): `'listing'` or `'detail'`.
    - `class` (`string`): Additional CSS class to append.
    - `width` (`string`): Override badge width (e.g. `'90px'`).

---

### `saleSchedulerPrice(element, options = [])`
*or `craft.saleScheduler.price(element, options)`*

Renders the active sale price, strikethrough original price, and single savings badge pill.

- **Parameters**:
  - `element` (`Product` | `Variant`): Target product or variant.
  - `options` (`array`):
    - `showOriginal` (`bool`): Toggle original price display.
    - `showSavings` (`bool`): Toggle savings pill (`Save $29.75 (35%)`).
    - `showBadge` (`bool`): Toggle custom promotional badge.
    - `class` (`string`): Custom CSS class.

**Output Structure**:
```html
<div class="sale-price-display sale-price-display--on-sale" data-sale-id="1">
    <span class="sale-price-display__current">$55.25</span>
    <span class="sale-price-display__original"><del>$85.00</del></span>
    <span class="sale-price-display__savings">Save $29.75 (35%)</span>
</div>
```

---

### `saleSchedulerCountdown(element, options = [])`
*or `craft.saleScheduler.countdown(element, options)`*

Renders the live urgency countdown timer. Pre-computes initial values on the server so there is zero initial layout shift or zero-flashing.

- **Parameters**:
  - `element` (`Product` | `Variant`): Target product or variant.
  - `options` (`array`):
    - `context` (`string`, default: `'detail'`): `'listing'` or `'detail'`.
    - `style` (`string`): Override countdown style (`'boxed'`, `'compact'`, or `'minimal'`). Defaults to sale configuration or plugin setting.
    - `heading` (`string`): Override custom heading text (e.g. `'Flash Sale Ends In:'`).
    - `headingColor` (`string`): Hex color for countdown heading and clock icon (e.g. `'#e11d48'`). Defaults to sale configuration or plugin setting.
    - `digitsColor` (`string`): Hex color for countdown numeric values and separators (e.g. `'#38bdf8'`). Defaults to sale configuration or plugin setting.
    - `class` (`string`): Custom CSS class wrapper.

The countdown dynamically injects the following CSS custom properties onto the `.sale-countdown` element:
```html
<div class="sale-countdown sale-countdown--boxed" 
     style="--sale-countdown-heading-color: #e11d48; --sale-countdown-digit-color: #38bdf8;" 
     data-sale-id="83" ...>
```

---

### `saleSchedulerBlock(element, options = [])`
*or `craft.saleScheduler.saleBlock(element, options)`*

Convenience all-in-one helper that renders the promotional tag, price, and countdown in a single block.

---

### `craft.saleScheduler.getSale(element)`

Returns the active `Sale` model instance for the given product or variant, or `null`.

```twig
{% set sale = craft.saleScheduler.getSale(product) %}
{% if sale %}
  <p class="banner">Sale ends on {{ sale.dateTo|date('M j, Y') }}!</p>
{% endif %}
```

---

### `craft.saleScheduler.getSaleInfo(element)`

Returns a calculation array with raw numbers and formatted currencies:

```twig
{% set info = craft.saleScheduler.getSaleInfo(product.defaultVariant) %}
{% if info.hasSale %}
  Original: {{ info.formattedOriginalPrice }}
  Sale Price: {{ info.formattedSalePrice }}
  Saved: {{ info.formattedSavingsAmount }} ({{ info.formattedSavingsPercent }})
{% endif %}
```

---

## 4. Countdown Timer Visual Styles

The plugin provides 3 distinct countdown visual styles designed to match modern storefront design systems:

### 1. Boxed Dark (`'boxed'`)
- **Visuals**: 4 rounded dark slate tiles (`#1e2530`), amber digits (`#f59e0b`), amber colons, and crisp uppercase white unit labels underneath (`DAYS`, `HOURS`, `MINUTES`, `SECONDS`).
- **Layout Behavior**: Built with `flex: 1 1 0%` and `clamp()` typography so it automatically flexes to fit tight product cards (~240px) up to full-width detail containers without horizontal overflow.

### 2. Compact (`'compact'`)
- **Visuals**: Modern cyan digits (`#00b4d8`) with inline unit letters (`d`, `h`, `m`, `s`) and cyan colons.
- **Layout Behavior**: Inline horizontal arrangement, ideal for notification bars, tight cards, or mobile viewports.

### 3. Minimal (`'minimal'`)
- **Visuals**: Grotesque thin typography (`font-weight: 300`) with subtle vertical divider lines (`|`) and title-case labels underneath.
- **Layout Behavior**: Clean, editorial aesthetic tailored for luxury apparel and architecture brands.

---

## 5. Client-Side JavaScript Lifecycle & Events

The countdown script runs completely autonomously on vanilla JS:
- Searches for all `[data-countdown-target]` elements.
- Decrements seconds accurately using client system time offsets.
- When a countdown timer reaches 0, the widget smoothly hides itself and emits a custom DOM event:

```javascript
document.addEventListener('salescheduler:expired', function(event) {
    console.log('Sale expired:', event.detail.saleId);
    
    // Optional: reload page or refresh cart via AJAX
    // window.location.reload();
});
```

---

## 6. CSS Customization & Overrides

You can customize any component in your project stylesheet:

```css
/* Custom savings pill badge styling */
.sale-price-display__savings {
    background: #ecfdf5;
    color: #047857;
    border-radius: 9999px;
    font-weight: 600;
}

/* Custom sale price color */
.sale-price-display--on-sale .sale-price-display__current {
    color: #dc2626;
    font-weight: 800;
}

/* Make countdown heading custom color */
.sale-countdown-timer__heading {
    color: #b45309;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
```
