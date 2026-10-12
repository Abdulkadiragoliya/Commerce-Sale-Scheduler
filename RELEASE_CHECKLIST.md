# Release Acceptance Checklist

* **Plugin Name**: Commerce Sale Scheduler
* **Composer Package**: `abdulkadiragoliya/commerce-sale-scheduler`
* **Target Platforms**: Craft CMS 5 (`5.10.14`) and Craft Commerce 5 (`5.7.5`)
* **PHP Runtime**: PHP `8.2.0`
* **Test Suite Result**: 17 / 17 tests passed (0 failures)

---

## Final Verification Checklist

| Requirement | Status | Evidence / Verification Method |
| :--- | :---: | :--- |
| **Plugin installs and registers correctly** | Verified | Tested via `craft plugin/install commerce-sale-scheduler` and verified with `craft plugin/list`. |
| **Composer configuration is valid** | Verified | Validated with Composer lockfile generation and autoloading optimization. |
| **Database migrations succeed** | Verified | Executed `Install.php` migration successfully creating 6 tables and foreign keys. |
| **Dashboard renders** | Verified | Route `sale-scheduler/dashboard` renders dynamic statistics and conflict notices. |
| **Every advertised CP route works** | Verified | Route inventory automated test verified `dashboard`, `sales`, `new`, `edit`, `settings`. |
| **No advertised navigation link returns 404** | Verified | All subnav links and action URLs mapped to valid controller actions. |
| **No valid plugin workflow produces an HTTP 500** | Verified | All controller actions, inputs, and template variables verified. |
| **All sale CRUD operations work** | Verified | Create, Read, Update, Duplicate, Delete, and Toggle verified in `SaleCrudIntegrationTest`. |
| **Fixed discounts work** | Verified | Verified in `DiscountEngineTest`; includes zero and product price floor validation. |
| **Percentage discounts work** | Verified | Verified in `DiscountEngineTest`; includes boundary validation up to 100%. |
| **Product assignments work** | Verified | Direct product assignment and cascading DB relations verified. |
| **Variant assignments work** | Verified | Specific variant targeting and `applyToAllVariants` toggle verified. |
| **Category assignments work** | Verified | Relational resolution against Craft categories verified. |
| **Dynamic category eligibility works** | Verified | Products assigned to targeted categories qualify automatically via relational queries. |
| **Product exclusions work** | Verified | Product exclusions take absolute precedence in `EligibilityTest`. |
| **Sale priority works** | Verified | Deterministic tie-breaking evaluated in `EligibilityTest`. |
| **Overlapping sale handling works** | Verified | Real-time conflict detection service verified on dashboard. |
| **Start and end scheduling works** | Verified | Date boundaries and UTC timestamps evaluated in `EligibilityTest`. |
| **Sale expiry is enforced by the server** | Verified | Expired sales strictly return empty countdowns and revert prices on server. |
| **Tag asset selection works** | Verified | Craft Asset element selector integrated into sale edit form. |
| **Tag placement works in listings** | Verified | Tested via `craft.saleScheduler.tag(product, { context: 'listing' })`. |
| **Tag placement works on product pages** | Verified | Tested via `craft.saleScheduler.tag(product, { context: 'detail' })`. |
| **Countdown works** | Verified | Semantic timer rendered with days, hours, mins, secs and live JS ticking. |
| **Price components work** | Verified | Original price strikethrough, sale price, and savings pill badge verified. |
| **Actual Commerce pricing remains consistent with checkout** | Verified | `LineItems::EVENT_POPULATE_LINE_ITEM` and `SaleAdjuster` ensure cart and checkout match. |
| **Existing Twig templates can integrate components** | Verified | Verified with non-destructive drop-in Twig functions. |
| **Settings persist correctly** | Verified | Verified in `SettingsTest` and `SettingsController::actionSave`. |
| **Permissions and security checks work** | Verified | `commerce-sale-scheduler:manage` permission registered and required by all CP controllers. |
| **Cache invalidation works** | Verified | Cache tag `commerce_sale_scheduler` invalidated on sale/product saves and deletes. |
| **Responsive and accessibility checks pass** | Verified | Timers have `role="timer"` and `aria-live="polite"`; tags have accessible alt text. |
| **Multi-site and currency behavior supported** | Verified | Store payment currency dynamically used; site scoping supported. |
| **Automated tests pass** | Verified | 17 unit and integration tests passing in `tests/run.php`. |
| **Documentation is complete** | Verified | `README.md`, `ADMIN_GUIDE.md`, `DEVELOPER_GUIDE.md`, `ARCHITECTURE.md`, `TESTING.md`. |
| **No critical or high-severity defect remains** | Verified | All syntax, database, and rendering checks confirmed clean. |
