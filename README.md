# Commerce Sale Scheduler

> Dynamic scheduled sales, category promotions, custom promotional tags, and live countdown timers for Craft Commerce 5.

[![Craft CMS 5](https://img.shields.io/badge/Craft%20CMS-5.x-red.svg)](https://craftcms.com)
[![Craft Commerce 5](https://img.shields.io/badge/Craft%20Commerce-5.x-blue.svg)](https://craftcms.com/commerce)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net)

Commerce Sale Scheduler is a production-grade promotion management plugin for **Craft CMS 5** and **Craft Commerce 5**. It enables merchants and administrators to plan, schedule, target, and display promotional sales across products and entire categories, complete with asset-backed visual promotional badges, server-authoritative countdown timers, and strikethrough price displays that seamlessly integrate into existing storefront templates.

---

## Key Features

- **Admin Dashboard**: Real-time overview of active, scheduled, expired, and disabled sales, conflict alerts, and sales approaching expiry.
- **Schedule Engine**: Start and end date scheduling with server-authoritative expiration enforcement down to the second.
- **Percentage & Fixed Discounts**: Support for percentage discounts (e.g., 20% off) and fixed amount discounts (e.g., $15.00 off), strictly validated to ensure prices never drop below zero.
- **Product & Specific Variant Targeting**: Apply sales to all product variants or target individual variants.
- **Dynamic Category Sales**: Target entire Craft category groups; newly categorized products are dynamically included without manual reconfiguration.
- **Product Exclusions**: Exclude specific products from category-wide sales with highest precedence.
- **Conflict & Precedence Resolution**: Deterministic tie-breaking hierarchy (`Variant > Product > Category > Priority > Expiration > ID`).
- **Promotional Image Tag Manager**: Select or upload promotional image badges (PNG, JPEG, WebP) with responsive positioning (`top-left`, `top-right`, `bottom-left`, `bottom-right`) and accessible alt text. Fallback to customizable text badges.
- **Lightweight Countdown Timer**: Days, hours, minutes, and seconds live timer with zero external JavaScript dependencies and 3 styles (`compact`, `boxed`, `minimal`).
- **Strikethrough Price Display**: Original price strikethrough, sale price, savings amount, savings percentage, and promotional pill badges.
- **Commerce Cart & Checkout Alignment**: Native Commerce `OrderAdjuster` and `LineItems::EVENT_POPULATE_LINE_ITEM` integration ensuring cart and checkout reflect active sale prices and immediately revert expired sales.
- **Zero Storefront Redesign Required**: Easy-to-use Twig functions designed to drop right into existing product cards and detail templates.
- **Craft Cloud Compatible**: Fully stateless, uses Craft's configured database, asset volumes, and caching APIs.

---

## Requirements

- **Craft CMS**: `^5.0.0`
- **Craft Commerce**: `^5.0.0`
- **PHP**: `^8.2.0`
- **Database**: MySQL 8.0+, MariaDB 10.5+, or PostgreSQL 13+

---

## Installation

### 1. Via Composer

From your Craft project root, add the repository and require the package:

```bash
composer require abdulkadiragoliya/commerce-sale-scheduler
```

### 2. Install the Plugin

Install via the Craft CLI:

```bash
php craft plugin/install commerce-sale-scheduler
```

Or navigate to **Settings &rarr; Plugins** in the Craft Control Panel and click **Install**.

---

## Quick Start (Storefront Twig Integration)

### Product Listing Card

In your existing product listing loop:

```twig
<div class="product-card has-sale-tag">
    <div class="product-card__media">
        {# Display promotional image tag badge #}
        {{ craft.saleScheduler.tag(product, { context: 'listing' }) }}

        <img src="{{ product.productImage.one().url ?? '' }}" alt="{{ product.title }}">
    </div>

    <h3>{{ product.title }}</h3>

    {# Display strikethrough original and sale price #}
    {{ craft.saleScheduler.price(product) }}
</div>
```

### Product Detail Page

On your existing product page:

```twig
<div class="product-detail">
    <div class="product-detail__gallery has-sale-tag">
        {{ craft.saleScheduler.tag(product, { context: 'detail' }) }}
        <img src="{{ product.productImage.one().url ?? '' }}" alt="{{ product.title }}">
    </div>

    <h1>{{ product.title }}</h1>

    {# Price display with savings info #}
    {{ craft.saleScheduler.price(product.defaultVariant) }}

    {# Live countdown timer #}
    {{ craft.saleScheduler.countdown(product.defaultVariant) }}

    {# Existing cart add form remains intact #}
    <form method="post" action="">
        {{ csrfInput() }}
        {{ actionInput('commerce/cart/update-cart') }}
        <input type="hidden" name="purchasableId" value="{{ product.defaultVariant.id }}">
        <input type="submit" value="Add to Cart">
    </form>
</div>
```

### All-in-One Sale Block

```twig
{{ craft.saleScheduler.saleBlock(product) }}
```

### Assets

Include the default CSS and lightweight JavaScript in your layout or template header/footer:

```twig
{{ craft.saleScheduler.css() }}
{{ craft.saleScheduler.js() }}
```

---

## Documentation

- [Admin Guide](docs/ADMIN_GUIDE.md) - Creating sales, setting up discounts, category rules, exclusions, and tag positioning.
- [Developer Guide](docs/DEVELOPER_GUIDE.md) - Twig API reference, custom styling, JavaScript events, and cart hooks.
- [Architecture](docs/ARCHITECTURE.md) - Precedence logic, database schema, conflict diagnostics, and pricing engine design.
- [Testing Guide](docs/TESTING.md) - Automated test suite instructions and route verification.
- [Release Checklist](RELEASE_CHECKLIST.md) - Quality acceptance criteria and verification status.

---

## License

Proprietary / Commercial. All rights reserved.
Developed by [Abdulkadir Agoliya](https://github.com/Abdulkadiragoliya).
