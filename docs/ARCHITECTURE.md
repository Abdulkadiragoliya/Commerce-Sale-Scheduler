# Commerce Sale Scheduler — Architecture & Design

This document details the architectural decisions, pricing calculation lifecycle, database design, and Commerce 5 integration patterns.

---

## 1. Commerce 5 Pricing Lifecycle Integration

A common vulnerability in discount plugins is implementing a "Twig-only" price reduction, where the storefront template renders a discounted price but the cart and checkout honor the original price, or vice-versa.

Commerce Sale Scheduler integrates directly with Craft Commerce 5's pricing and purchasable lifecycle:

1. **Purchasable Population**: When a purchasable is loaded or populated into an order (`LineItems::EVENT_POPULATE_LINE_ITEM`), the plugin's `Eligibility` service evaluates the current active scheduled sale.
2. **Promotional Price Setting**: If an active sale applies, `$lineItem->setPromotionalPrice($discountedPrice)` is called. In Commerce 5, LineItem's `salePrice` automatically returns `promotionalPrice ?? price`, ensuring line item subtotals, taxes, and order totals reflect the scheduled sale.
3. **Order Recalculation via OrderAdjuster**: The plugin registers a native `SaleAdjuster` (`craft\commerce\base\AdjusterInterface`). Whenever the cart is recalculated, the adjuster re-verifies whether scheduled sales are still active. If a sale expired while an item sat in the customer's cart, the price immediately reverts back to standard price.
4. **Authoritative Server Expiration**: JavaScript countdown timers are strictly cosmetic. Expiration is verified against the server's UTC clock on every request and order calculation.

---

## 2. Precedence Hierarchy & Conflict Resolution

To avoid accidental discount stacking and maintain deterministic pricing, only **one winning sale** from this plugin applies to a purchasable at any moment.

Precedence is evaluated as follows:

```
+-----------------------------------------------------------+
| 1. Exclusions Check                                       |
|    Product in exclusionIds? -> ELIGIBILITY REJECTED       |
+-----------------------------------------------------------+
                             |
                             v
+-----------------------------------------------------------+
| 2. Specificity Ranking                                    |
|    Rank 3: Direct Variant Target                          |
|    Rank 2: Direct Product Target                          |
|    Rank 1: Category Match (Dynamic Category Inclusion)    |
+-----------------------------------------------------------+
                             |
                             v
+-----------------------------------------------------------+
| 3. Competing Sales Tie-Breaking                           |
|    a. Higher Specificity Rank wins                        |
|    b. Higher explicit Priority (integer) wins             |
|    c. Earliest Expiration Date (dateTo ASC) wins          |
|    d. Latest Created (id DESC) wins                       |
+-----------------------------------------------------------+
```

---

## 3. Dynamic Category Sales

Rather than snapshotting product IDs into a static list at sale creation time, category sales are evaluated **dynamically**:

- The sale record stores target category IDs in `commerce_salescheduler_sale_categories`.
- When evaluating eligibility, the plugin checks relational links between the product and category groups (`{{%relations}}` and Category queries).
- When a merchant adds a new product to a category, that product immediately inherits the category sale without requiring the administrator to edit or save the sale.

---

## 4. Database Schema

All database tables follow Craft 5 conventions and use the `commerce_salescheduler_` prefix:

- `{{%commerce_salescheduler_sales}}`: Primary table storing sale configurations, discount definitions, schedules, tag positioning, and timer styles.
- `{{%commerce_salescheduler_sale_products}}`: Junction table for direct product targeting.
- `{{%commerce_salescheduler_sale_variants}}`: Junction table for specific variant targeting.
- `{{%commerce_salescheduler_sale_categories}}`: Junction table for category targeting.
- `{{%commerce_salescheduler_sale_exclusions}}`: Junction table for product exclusions.
- `{{%commerce_salescheduler_sale_sites}}`: Junction table for multi-site assignments.

All junction tables use `ON DELETE CASCADE` foreign keys referencing `{{%commerce_salescheduler_sales}}` and `{{%elements}}`, ensuring that when products, variants, or categories are removed, junction rows are cleanly pruned without orphaned data.

---

## 5. Cache Strategy & Invalidation

- **Tagging**: Storefront Twig templates and queries utilize the tag `commerce_sale_scheduler`.
- **Automatic Invalidation**: Whenever a sale is created, updated, toggled, duplicated, or deleted, `CacheService::invalidateAll()` clears the tag.
- **Element Hook Invalidation**: Whenever products or categories are saved or deleted via Craft Control Panel or feed imports, cache invalidation is triggered.

---

## 6. Craft Cloud Compatibility

- **Stateless Operation**: No persistent local filesystem dependencies.
- **Supported APIs**: Strictly uses Craft's configured database connection, Craft Asset Volumes, and native Twig extension APIs.
- **Zero Daemons / Long-Running Processes**: Scheduled sales depend strictly on authoritative timestamp evaluation at request time, meaning delayed background workers will never result in expired discounts being honored.
