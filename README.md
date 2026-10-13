# Commerce Sale Scheduler

> Dynamic scheduled sales, category promotions, custom promotional tags, and responsive live countdown timers for Craft Commerce 5.

[![Craft CMS 5](https://img.shields.io/badge/Craft%20CMS-5.x-red.svg)](https://craftcms.com)
[![Craft Commerce 5](https://img.shields.io/badge/Craft%20Commerce-5.x-blue.svg)](https://craftcms.com/commerce)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-green.svg)](#license)

**Commerce Sale Scheduler** is a production-grade promotion and urgency management plugin for **Craft CMS 5** and **Craft Commerce 5**. It enables store owners and merchandisers to easily schedule flash sales, percentage discounts, fixed-amount markdowns, dynamic category campaigns, promotional image badges, and live countdown timers — all seamlessly integrated with Craft Commerce's cart, checkout, and line item architecture.

---

## Key Features

- ⏱️ **Scheduled Sales Engine**: Start and end date scheduling down to the second with server-authoritative expiration enforcement.
- 💰 **Flexible Discount Models**: Percentage discounts (e.g., `35% OFF`) and fixed-amount discounts (e.g., `$20.00 OFF`) with built-in zero-floor protection.
- 🎯 **Granular Targeting**: Target all product variants, specific individual variants, or entire Craft category groups.
- 🔄 **Dynamic Category Eligibility**: Products assigned to targeted categories qualify automatically — zero manual re-indexing required.
- 🚫 **High-Precedence Exclusions**: Exclude specific high-margin or clearance products from storewide or category-wide promotions.
- ⚖️ **Conflict & Precedence Engine**: Deterministic resolution hierarchy (`Variant > Product > Category > Priority > Expiration > ID`).
- 🎨 **Visual Tag & Badge Manager**: Overlay custom asset-backed promotional badges (PNG, JPEG, WebP) or CSS text pills on product cards and detail galleries across 4 corner positions.
- ⏳ **3 Live Countdown Timer Themes**:
  - **Boxed Dark**: Modern dark slate tiles with amber digits, amber colons, and uppercase labels underneath. Auto-scales fluidly to fit inside any container without overflow.
  - **Compact**: Cyan digits with inline unit letters (`d`, `h`, `m`, `s`) and cyan colons.
  - **Minimal**: Thin typography with subtle vertical divider lines.
- 🏷️ **Clean Storefront Pricing**: Shows active sale price, strikethrough original price, and a single consolidated savings pill (e.g. `Save $29.75 (35%)`) with optional custom badge overrides.
- 🛒 **Native Commerce Cart & Checkout Sync**: Order adjuster and line item snapshot integration ensures the price on the page matches the bag and the final invoice.
- ⚡ **Zero External Dependencies**: Pure vanilla JavaScript timer with zero npm or library dependencies.

---

## Requirements

- **Craft CMS**: `^5.0.0` (or `^4.0.0`)
- **Craft Commerce**: `^5.0.0` (or `^4.0.0`)
- **PHP**: `^8.2.0`
- **Database**: MySQL 8.0+, MariaDB 10.5+, or PostgreSQL 13+

---

## Installation

### Option A: Via Composer (Packagist)
```bash
composer require abdulkadiragoliya/commerce-sale-scheduler
php craft plugin/install commerce-sale-scheduler
```

### Option B: Local / Downloaded Plugin Installation
If you download or copy this plugin folder directly into another Craft project (e.g., inside `plugins/commerce-sale-scheduler/`):

1. Add a local path repository to your project's root `composer.json`:
   ```json
   "repositories": [
     {
       "type": "path",
       "url": "plugins/commerce-sale-scheduler"
     }
   ]
   ```
2. Require the package via Composer:
   ```bash
   composer require abdulkadiragoliya/commerce-sale-scheduler:*
   ```
3. Install and run database migrations via Craft CLI:
   ```bash
   php craft plugin/install commerce-sale-scheduler
   ```
   Or navigate to **Settings → Plugins** in the Craft Control Panel and click **Install**.

---

## Developer Integration Guide: Exactly Where to Put Code

Integrating Commerce Sale Scheduler into your storefront templates takes only a few minutes. You do not need to rewrite your templates or recalculate prices.

Both Twig calling styles are supported:
- **Global Helper Functions**: `saleSchedulerCss()`, `saleSchedulerJs()`, `saleSchedulerTag()`, `saleSchedulerPrice()`, `saleSchedulerCountdown()`
- **Craft Variable Syntax**: `craft.saleScheduler.css()`, `craft.saleScheduler.js()`, `craft.saleScheduler.tag()`, etc.

---

### Step 1: Base Layout Template (`templates/_layout.twig`)

Load the plugin's CSS stylesheet in your `<head>` and the lightweight timer script before the closing `</body>` tag.

```twig
{# templates/_layout.twig #}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{% block title %}My Store{% endblock %}</title>

    {# 1. Include Sale Scheduler Stylesheet #}
    {{ saleSchedulerCss() }}
</head>
<body>

    {% block content %}
    {% endblock %}

    {# 2. Include Sale Scheduler Lightweight JavaScript #}
    {# Place this outside child blocks so it is never suppressed #}
    {{ saleSchedulerJs() }}
</body>
</html>
```

---

### Step 2: Product Listing / Catalog Page (`templates/shop/index.twig`)

In your product card loop, add the **promotional tag badge** over the product image and replace the static price with the dynamic **sale price component**:

```twig
{# templates/shop/index.twig or templates/_includes/product-card.twig #}
<div class="product-grid">
  {% for product in products %}
    {% set variant = product.defaultVariant %}

    <article class="product-card">
      {# Wrap image in a container with .has-sale-tag for relative badge positioning #}
      <div class="product-card__image-wrap has-sale-tag" style="position: relative;">
        
        {# A. Promotional Tag Badge (Asset Image or Text Badge) #}
        {{ saleSchedulerTag(product, { context: 'listing' }) }}

        <a href="{{ product.url }}">
          <img src="{{ product.productImages.one().getUrl() ?? '' }}" alt="{{ product.title }}">
        </a>
      </div>

      <div class="product-card__info">
        <h3><a href="{{ product.url }}">{{ product.title }}</a></h3>

        {# B. Strikethrough Original + Sale Price + Savings Pill #}
        {{ saleSchedulerPrice(variant) }}

        {# C. (Optional) Listing Countdown Timer #}
        {# Only renders if 'Show on Product Listing Cards' is enabled in the sale #}
        {{ saleSchedulerCountdown(variant, { context: 'listing' }) }}
      </div>
    </article>
  {% endfor %}
</div>
```

---

### Step 3: Product Detail Page (`templates/shop/_product.twig`)

On your product detail page, add the promotional tag over the primary gallery image, the dynamic price component above the Add-to-Cart form, and the live countdown urgency timer:

```twig
{# templates/shop/_product.twig #}
{% extends "_layout.twig" %}

{% block content %}
<div class="product-detail">
  
  <!-- Left: Main Image Gallery -->
  <div class="product-gallery">
    <div class="product-main-image-wrap has-sale-tag" style="position: relative;">
      
      {# 1. Promotional Tag Badge #}
      {{ saleSchedulerTag(product, { context: 'detail' }) }}

      <img src="{{ product.productImages.one().getUrl() }}" alt="{{ product.title }}">
    </div>
  </div>

  <!-- Right: Purchasing Details -->
  <div class="product-info">
    <h1>{{ product.title }}</h1>

    {# 2. Sale Price Display with Savings Pill #}
    {# Outputs: $55.25  ~~$85.00~~  Save $29.75 (35%) #}
    <div class="product-price-section">
      {{ saleSchedulerPrice(product.defaultVariant) }}
    </div>

    {# 3. Urgency Countdown Timer Box #}
    {# Renders Days : Hours : Mins : Secs with live client-side countdown #}
    {{ saleSchedulerCountdown(product.defaultVariant, { context: 'detail' }) }}

    {# 4. Standard Commerce Add-to-Cart Form (NO changes needed!) #}
    <form method="post" action="">
      {{ csrfInput() }}
      {{ actionInput('commerce/cart/update-cart') }}
      <input type="hidden" name="purchasableId" value="{{ product.defaultVariant.id }}">
      <input type="number" name="qty" value="1" min="1">
      <button type="submit" class="btn btn-primary">Add to Bag</button>
    </form>
  </div>

</div>
{% endblock %}
```

---

### Step 4: Cart and Checkout (Automatic!)

**No frontend code required!**

When a customer adds a discounted product to their bag:
1. The plugin's `LineItems::EVENT_POPULATE_LINE_ITEM` hook intercepts the line item and applies the scheduled sale price.
2. The plugin's `SaleAdjuster` ensures that if a sale expires while an item is in the customer's cart, the price automatically reverts to normal on checkout.
3. Sale ID and campaign details are stored safely in the line item snapshot for accounting and order reporting.

---

## Countdown Timer Styles

The plugin includes 3 distinct visual countdown styles that can be chosen per sale in the Control Panel, or overridden directly in Twig:

```twig
{# Override style in Twig #}
{{ saleSchedulerCountdown(product.defaultVariant, { style: 'boxed' }) }}
{{ saleSchedulerCountdown(product.defaultVariant, { style: 'compact' }) }}
{{ saleSchedulerCountdown(product.defaultVariant, { style: 'minimal' }) }}
```

| Style | Appearance & Characteristics | Ideal Placement |
| :--- | :--- | :--- |
| **Boxed Dark** (`'boxed'`) | **4 rounded dark slate tiles** (`#1e2530`), amber digits (`#f59e0b`), amber colons, and uppercase white labels (`DAYS`, `HOURS`, `MINUTES`, `SECONDS`). Designed with fluid `flex: 1 1 0%` scaling to fit product cards without overflowing. | Product detail pages, hero sale sections, promotional landing pages. |
| **Compact** (`'compact'`) | **Cyan digits** (`#00b4d8`) with inline short suffix letters (`d`, `h`, `m`, `s`) and cyan colons. Compact horizontal footprint. | Product listing cards, announcement top-bars, mobile sidebars. |
| **Minimal** (`'minimal'`) | **Thin grotesque typography** (`font-weight: 300`) with subtle vertical divider lines and muted title-case labels. Clean and unobtrusive. | Luxury brands, minimalist lookbooks, editorial layouts. |

---

## Price Display Options

`saleSchedulerPrice(element, options)` gives you full control over how promotional prices are presented:

```twig
{# Default: uses settings configured in the sale #}
{{ saleSchedulerPrice(product.defaultVariant) }}

{# Custom overrides #}
{{ saleSchedulerPrice(product.defaultVariant, {
    showOriginal: true,     {# Show strikethrough original price #}
    showSavings: true,      {# Show 'Save $29.75 (35%)' pill #}
    showBadge: false,       {# Hide optional custom promo badge #}
    class: 'my-price-wrap'  {# Custom CSS class #}
}) }}
```

### Rendered Output
- **Single Consolidated Badge**: Avoids messy duplicates. It outputs clean, unified pricing:
  ```html
  <div class="sale-price-display sale-price-display--on-sale" data-sale-id="1">
      <span class="sale-price-display__current">$55.25</span>
      <span class="sale-price-display__original"><del>$85.00</del></span>
      <span class="sale-price-display__savings">Save $29.75 (35%)</span>
  </div>
  ```

---

## Control Panel Features

Navigate to **Sale Scheduler** in the Craft Control Panel to access:

1. **Dashboard** (`/admin/sale-scheduler/dashboard`):
   - Active, scheduled, and expired sales counters.
   - Real-time conflict notifications with diagnostic resolution explanations.
   - Live list of sales approaching expiry within 48 hours.

2. **Sales Management** (`/admin/sale-scheduler/sales`):
   - Searchable, filterable list of all promotional campaigns.
   - Quick **Enable/Disable** toggle switches.
   - **Duplicate Sale** action for rapid seasonal campaign creation.
   - Bulk deletion and status updating.

3. **Sale Editor** (`/admin/sale-scheduler/sales/<id>`):
   - **Discount Type**: Percentage (%) or Fixed Amount ($).
   - **Scheduling**: Precise start and end datetime pickers.
   - **Targeting**: Products, individual variants, category trees, and product exclusions.
   - **Promotional Badge**: Asset image selector, placement (`top-right`, `top-left`, etc.), width, and alt text.
   - **Countdown Timer Settings**:
     - Enable countdown timer switch.
     - Style selector (`boxed`, `compact`, `minimal`).
     - Customizable **Countdown Heading** text (e.g. "Hurry, Sale Ends In:").
     - **Countdown Heading Font Color** picker (e.g. `#e11d48`).
     - **Countdown Style Font / Digits Color** picker (e.g. `#38bdf8`).
     - Separate visibility toggles for Product Detail Pages vs. Product Listing Cards.
   - **Storefront Price Display Options**:
     - **Show Strikethrough Original Price** toggle (enable/disable strikethrough original price display).
     - **Show Savings Amount & Percent** toggle (enable/disable the consolidated "Save $X (Y%)" pill).
     - **Show Promo Badge** toggle (enable/disable promo badge display).
     - Optional **Custom Discount Badge Text** (override with custom copy like "SPECIAL DEAL").

4. **Settings** (`/admin/sale-scheduler/settings`):
   - Global default countdown style (`boxed`, `compact`, `minimal`).
   - Global default **Countdown Heading Font Color** and **Countdown Style Font / Digits Color**.
   - Storefront CSS and JS asset auto-injection toggle.
   - Cache TTL configuration.

---

## Documentation

- [Admin Guide](docs/ADMIN_GUIDE.md) — Comprehensive guide to scheduling, category rules, exclusions, and conflict resolution.
- [Developer Guide](docs/DEVELOPER_GUIDE.md) — Full Twig API, JavaScript events, CSS class hooks, and cart internals.
- [Architecture](docs/ARCHITECTURE.md) — Precedence logic, database schema, conflict diagnostics, and pricing engine design.
- [Testing Guide](docs/TESTING.md) — Running the automated test suite and verifying routes.
- [Release Checklist](RELEASE_CHECKLIST.md) — Acceptance criteria and Plugin Store submission verification.

---

## License

Proprietary / Commercial. All rights reserved.  
Developed by [Abdulkadir Agoliya](https://github.com/Abdulkadiragoliya).
