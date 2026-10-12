# Commerce Sale Scheduler — Testing Guide

This document describes how to execute automated tests, route verification, and manual validation for Commerce Sale Scheduler.

---

## 1. Automated Test Runner

The repository includes a standalone test harness that bootstraps Craft CMS 5, Yii, and Craft Commerce 5 to execute unit and integration test suites.

### Running the Test Suite

From the plugin directory, execute:

```bash
php tests/run.php
```

### Test Suite Structure

```
tests/
├── bootstrap.php                  # Craft 5 console application bootstrapper
├── TestCase.php                   # Assertion harness
├── run.php                        # CLI test runner
└── unit/
    ├── DiscountEngineTest.php     # Percentage & fixed discount calculations, zero/negative bounds
    ├── EligibilityTest.php        # Start/end dates, specificity ranks, exclusion precedence
    ├── SaleModelTest.php          # Validation rules, attribute assignments, status labels
    ├── SettingsTest.php           # Default settings values & validation
    ├── TagManagerTest.php         # Tag badge rendering, fallback text badges, placement classes
    ├── CountdownTest.php          # Timer rendering, units, server-authoritative expiration
    ├── SaleCrudIntegrationTest.php # Database CRUD, duplication, cascades, and relation persistence
    ├── RouteVerificationTest.php  # Control panel route resolution, controller actions, Twig template rendering
    └── TwigExtensionTest.php      # Full Twig API, published CSS and JS bundles
```

---

## 2. Route & Controller Action Inventory

Every registered Control Panel route and controller action has been mapped and verified:

| CP Route | Controller Action | Template | Status |
| :--- | :--- | :--- | :--- |
| `sale-scheduler` | `DashboardController::actionIndex()` | `commerce-sale-scheduler/dashboard/index.twig` | Verified (200 OK) |
| `sale-scheduler/dashboard` | `DashboardController::actionIndex()` | `commerce-sale-scheduler/dashboard/index.twig` | Verified (200 OK) |
| `sale-scheduler/sales` | `SalesController::actionIndex()` | `commerce-sale-scheduler/sales/index.twig` | Verified (200 OK) |
| `sale-scheduler/sales/new` | `SalesController::actionEdit()` | `commerce-sale-scheduler/sales/_edit.twig` | Verified (200 OK) |
| `sale-scheduler/sales/create`| `SalesController::actionEdit()` | `commerce-sale-scheduler/sales/_edit.twig` | Verified (200 OK) |
| `sale-scheduler/sales/<id>` | `SalesController::actionEdit()` | `commerce-sale-scheduler/sales/_edit.twig` | Verified (200 OK) |
| `sale-scheduler/settings` | `SettingsController::actionIndex()` | `commerce-sale-scheduler/settings/index.twig` | Verified (200 OK) |
| Action `save` | `SalesController::actionSave()` | Redirect to `sale-scheduler/sales` | Verified (POST) |
| Action `duplicate` | `SalesController::actionDuplicate()` | Redirect to `sale-scheduler/sales/edit` | Verified (POST) |
| Action `delete` | `SalesController::actionDelete()` | Redirect to `sale-scheduler/sales` | Verified (POST) |
| Action `toggle-status` | `SalesController::actionToggleStatus()` | JSON response / Flash redirect | Verified (POST) |
| Action `bulk-action` | `SalesController::actionBulkAction()` | Redirect to `sale-scheduler/sales` | Verified (POST) |
| Action `preview` | `SalesController::actionPreview()` | JSON calculation payload | Verified (GET) |
| Action `settings/save` | `SettingsController::actionSave()` | Redirect to `sale-scheduler/settings` | Verified (POST) |

---

## 3. Manual Verification Checklist

1. **Dashboard Check**:
   - Access `/admin/sale-scheduler`.
   - Verify metrics cards display accurate counts.
2. **Sale Creation**:
   - Create a 20% discount sale on a test product.
   - Set start date in the past, end date 3 days in the future.
   - Confirm sale appears on listing with "Active" badge.
3. **Storefront Verification**:
   - Visit product page and observe `{{ craft.saleScheduler.tag(product) }}` badge.
   - Observe `{{ craft.saleScheduler.price(product) }}` showing original strikethrough and sale price.
   - Observe `{{ craft.saleScheduler.countdown(product) }}` ticking down.
4. **Cart Verification**:
   - Add product to cart.
   - Confirm cart subtotal and unit price reflect the 20% discount.
5. **Expiration Enforcement**:
   - Edit the sale and set end date in the past.
   - Refresh product page: timer disappears, regular price returns.
   - Refresh cart: price reverts to original price via `SaleAdjuster`.
