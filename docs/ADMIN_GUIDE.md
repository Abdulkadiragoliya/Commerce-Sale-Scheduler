# Commerce Sale Scheduler — Administrator Guide

This guide walks store administrators through managing sales, discounts, promotional badges, and countdown timers using the Craft CMS Control Panel.

---

## 1. Navigating the Control Panel

Once installed, **Sale Scheduler** appears in the main Control Panel sidebar navigation:

- **Dashboard** (`/admin/sale-scheduler`): High-level operational overview.
- **Sales** (`/admin/sale-scheduler/sales`): List, search, filter, duplicate, and manage promotional sales.
- **Settings** (`/admin/sale-scheduler/settings`): Configure global store defaults for tag badges, countdown styles, and cache duration.

---

## 2. Dashboard Overview

The dashboard displays real-time, database-backed metrics:

- **Total Sales**: All sales created across all statuses.
- **Active Sales**: Sales currently running based on schedule and enabled state.
- **Scheduled**: Future sales waiting for their start date.
- **Expiring Soon (48h)**: Active sales expiring within the next 48 hours.
- **Pricing Conflicts**: Alerts for overlapping sales competing for the same products or categories.

---

## 3. Creating a Sale

To create a new promotion:

1. Go to **Sale Scheduler &rarr; Sales**.
2. Click **Create Sale**.
3. Fill out the following sections:

### General Details
- **Sale Name**: Internal name used in listings and default badges (e.g., "Black Friday 2026").
- **Internal Description**: Optional notes about the promotion.
- **Enabled**: Lightswitch to turn the sale on or off.
- **Priority**: Integer value. When a product matches multiple competing sales, the sale with the higher priority number wins (e.g., `10` beats `5`).

### Schedule
- **Start Date & Time**: When the promotion automatically goes live. If left blank, the sale begins immediately upon saving.
- **End Date & Time**: When the promotion ends. Once passed, prices immediately revert and timers disappear.

### Discount Configuration
- **Discount Type**:
  - `Percentage (%) Off`: Deducts a percentage (e.g., `20` = 20% off).
  - `Fixed Amount Off`: Deducts a flat currency amount (e.g., `15.00` = $15 off).
- **Discount Value**: Numerical discount value.
  * *Safety Note*: Fixed discounts automatically cap at the product's regular price; prices can never drop below $0.00.

### Targeting & Assignments
- **Target Products**: Select one or more specific products.
- **Apply to All Variants**: When enabled, all variants belonging to the selected products receive the discount.
- **Target Specific Variants**: Select specific variants if you want only particular SKUs discounted.
- **Target Categories (Dynamic)**: Select one or more Craft categories. Any product assigned to these categories automatically qualifies for the sale. If you add new products to the category later, they are included automatically!
- **Product Exclusions**: Select products that should be excluded from the sale even if they belong to targeted categories.

### Tag & Badge Settings
- **Enable Tag Badge**: Toggle display of promotional image or text badge.
- **Promotional Tag Image Asset**: Upload or select an image asset (PNG, JPEG, WebP) from Craft Asset Volumes.
- **Tag Width**: Specify CSS width (e.g., `80px` or `25%`).
- **Tag Label / Text Badge**: Fallback text displayed if no image asset is selected (e.g., `FLASH SALE` or `25% OFF`).
- **Position on Listing Cards**: Placement on catalog cards (`Top Right`, `Top Left`, `Bottom Right`, `Bottom Left`).
- **Position on Product Detail Page**: Placement over product gallery images.

### Countdown Timer Settings
- **Enable Countdown Timer**: Toggle display of the live timer.
- **Countdown Style**:
  - `Compact`: Clean inline layout.
  - `Boxed`: Dark themed numerical boxes.
  - `Minimal`: Subtle typography.
- **Countdown Heading**: Custom label (e.g., "Hurry, Sale Ends In:").
- **Show on Product Detail**: Enable for single product pages.
- **Show on Listing Cards**: Enable for catalog cards (optional).

### Price Display Options
- **Show Strikethrough Original Price**: Shows `<del>$100.00</del>` next to `$80.00`.
- **Show Savings Amount & Percent**: Shows "Save $20.00 (20%)".
- **Show Promo Badge**: Displays a pill badge with custom or automatic savings text.

---

## 4. Precedence & Conflict Rules

When multiple sales exist simultaneously, the plugin resolves the single winning sale using strict precedence:

1. **Exclusions**: If a product is listed in `Product Exclusions`, it will **never** receive that sale.
2. **Direct Variant Targeting**: A sale specifically assigned to a variant beats a product-wide or category-wide sale.
3. **Direct Product Targeting**: A sale directly assigned to a product beats a category-wide sale.
4. **Category Targeting**: Applied dynamically to products in matching categories.
5. **Priority**: Higher priority number wins between sales at the same targeting level.
6. **Tie-Breaking**: If priorities match, the sale ending earliest wins.

---

## 5. Duplicating & Managing Sales

- **Duplicate**: Click **Duplicate** on any sale row to create an exact copy as a draft (`Disabled` by default), allowing you to easily adjust dates or products without rebuilding from scratch.
- **Bulk Actions**: Select multiple sales via checkboxes to enable, disable, or delete in one step.
