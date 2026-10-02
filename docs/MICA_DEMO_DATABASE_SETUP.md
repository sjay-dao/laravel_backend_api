# Reproducible Mica demo database

This milestone makes migrations the installation source and provides a small fictional
dataset for the existing Mica inventory, POS, payment, completion, receipt, dashboard
and expense workflows. It does not copy development transactions or build new business rules.

## Installation and reset

Provision a **new, dedicated** MySQL/MariaDB database named `mica_demo` (or
`mica_demo_<suffix>`). Configure the demo instance separately from development:

```dotenv
APP_ENV=demo
DEMO_MODE=true
DB_CONNECTION=mysql
DB_DATABASE=mica_demo
DEMO_DATABASE=mica_demo
DEMO_ANCHOR_DATE=2026-10-02
```

Set that instance's own database credentials and a newly generated Laravel APP_KEY.
Do not reuse this file for the current `laravel_backend_api` database. Remove stale
configuration/route caches when changing environments. For a live demonstration, set
DEMO_ANCHOR_DATE to the presentation's date before the initial seed/reset; the default
date is fixed for reproducibility. The application clock and dashboard formulas are unchanged.

For a new empty database:

```powershell
php8 artisan migrate --force --no-interaction
php8 artisan db:seed --class=MicaDemoDatabaseSeeder --force --no-interaction
php8 artisan migrate:status
```

`DatabaseSeeder` selects the same demo path when DEMO_MODE is enabled. Otherwise it
seeds reference data only, without demo users or transactions.

To deliberately erase and rebuild the dedicated demo:

```powershell
php8 artisan demo:reset --force --no-interaction
```

The command warns before deletion and refuses without `--force`. It requires explicit
DEMO_MODE=true, permits only APP_ENV=local/testing/demo, rejects production, and requires an exact DEMO_DATABASE/connection-name
match with a `mica_demo` prefix, verifies both physical write/read database identities,
and checks the schema builder uses the same connection. SQLite is allowed only as
`:memory:` in testing. These guards also protect every demo-specific seeder.
They never authorize resetting a development or production database.

## Demo accounts and authorization

| Role | Email | Password | Permissions |
| --- | --- | --- | --- |
| Administrator | admin@mica.demo | MicaDemo2026! | All 87 catalog permissions |
| Manager | manager@mica.demo | MicaDemo2026! | 36 inventory/sales/finance/reference/dashboard permissions |
| Cashier | cashier@mica.demo | MicaDemo2026! | 9 POS/lookup permissions listed below |

Passwords are hashed with Laravel Hash. These identities are fictional and intentionally
public demo credentials; guarded seeders refuse production. There are seven role records:
the six existing generic roles plus cashier. Non-demo customer, supplier, branch_staff
and delivery_rider roles receive no automatic broad grants. The existing employee policy
also has its 14 dependency-ordered role/ability records for admin/manager/branch_staff.

Cashier grants:

```text
sales.sales.view
sales.sales.create
sales.sales.update
inventory.products.view
inventory.lots.view
reference.branches.view
reference.customers.view
reference.customers.create
reference.suppliers.view
```

The catalog contains the actual literal backend/frontend abilities, including existing
employee naming variants and the Evidence catalog; it is explicit seed data, not a runtime
source-code scanner. The permission catalog is documented in `MICA_DEMO_PERMISSION_CATALOG.md`.
Confirmation/payment/completion/cancellation require the existing sales.sales.update
permission at the API, and sales reads require sales.sales.view. No Admin bypass was added.
Cashier cannot receive stock or create expenses. Repeat seeding preserves other assignments
and does not truncate permission tables or overwrite users by numeric ID.

## Dataset and dependency order

Reference types/statuses/units → permission catalog → roles/grants → guarded demo users
→ branch/warehouse → dealers/categories/products/base units/prices → received lots and
movements → customers → service-created historical sales/payments/allocations → expenses.

The initial reset produces **511 rows in 91 tables**, including 54 migration ledger rows.
Empty auth-token/session/cache/queue/Evidence/legacy transaction tables contribute zero rows.

| Major data | Count |
| --- | ---: |
| Users / user-role links | 3 / 3 |
| Branches / warehouses | 1 / 1 |
| Customers (including Walk-in Customer) / dealers | 4 / 2 |
| Inventory categories / products / base-unit mappings | 4 / 11 / 11 |
| Units / wholesale tiers | 5 / 3 |
| Lots / inventory movements / movement items / lot movement links | 14 / 18 / 19 / 19 |
| Sales / lines / allocations / payment records | 8 / 8 / 5 / 6 |
| Expenses | 5 |
| Roles / permissions / role-permission links | 7 / 87 / 132 |
| Lookup types / lookups / employee policy grants | 13 / 52 / 14 |
| Geography / legacy products / legacy orders / Evidence records | 0 / 0 / 0 / 0 |

Products: CityRide E1, UrbanVolt X2, CargoMax C3, 48V 20Ah Battery, 60V 20Ah Battery,
Brake Pad Set, Controller 48V, Throttle Assembly, Rear Basket, Phone Holder and Rain Cover.
Dealers: Demo Dealer A — Northstar Mobility; Demo Dealer B — Bluewheel Trading.

UrbanVolt originally receives 5 owned, 4 Dealer A and 3 Dealer B units. Its historical
wholesale sale consumes 1 owned and 2 Dealer A units, leaving **4 owned + 2 Dealer A +
3 Dealer B = 9 available**. Retail is ₱45,000; tiers are 3–5: ₱42,500, 6–10: ₱40,500,
11+: ₱39,000 per unit, stored in cents. The 11+ pricing tier exists even though 11 units
cannot currently be fulfilled; stock rules are not bypassed. Other products cover owned-only,
consignment-only and mixed sources. Receiving movements predate historical sales.

Four CLOSED orders use existing services for snapshots, payments, exact lot allocation
and stock movements. One confirmed order is partially paid, one confirmed order unpaid,
one is a discounted draft and one cancelled. Payment examples cover cash/change, GCash,
bank transfer and split payments. At the default anchor date, today's sales are ₱166,000
and monthly sales ₱184,500. Prior-month CargoMax revenue is excluded from the current month.
The five fictional expense categories are rent, utilities, transport, supplies and maintenance.

Geography is intentionally empty: the demo's branch/customer/dealer geography links are
nullable and the POS needs existing names/IDs rather than PSGC selection. No fabricated
official PSGC codes or 42,000-row geography import is introduced. Broader geographic
address entry needs a separately sourced legitimate reference dataset.

## Migration repairs and archive

- Remove invalid AFTER from products CREATE TABLE.
- Defer legacy order address/branch foreign keys until targets exist.
- Match geography IDs to the existing signed INT links.
- Defer employee lookup foreign key; add the missing users.employee_id relationship.
- Create missing goods_receipts/items before P2P alterations; existing tables are preserved.
- Create Unit's actual measurement_type/is_base API schema early; make the redundant late
  Unit migration harmless; add a guarded additive alignment migration for old type-only schemas.
  Known legacy dimensions are mapped; unknown values remain null and old IDs/type fields remain.
- Align supported inventory object field lengths/nullability and add roles.is_active.
- Correct the schedules rollback table name.
- Preserve explicit short ownership-allocation and wholesale-index names required by MariaDB.

New migrations:

```text
2026_06_25_100000_add_deferred_legacy_order_foreign_keys.php
2026_07_28_060000_add_deferred_employee_lookup_foreign_key.php
2026_08_18_120000_create_missing_goods_receipt_foundation.php
2026_10_02_000000_add_role_active_flag.php
2026_10_02_010000_align_unit_api_columns.php
```

The obsolete auto-loaded `database/schema/mysql-schema.sql` is preserved in
`docs/legacy-schema/mysql-schema.sql` with an explicit archive warning. No SQL dump is
an installation source. Existing deployed migration ledger entries are not rewritten.
Shared foundation rollback methods intentionally preserve tables/data; this is not a
promise that rolling the whole legacy migration history down is safe. Restore a backup
or revert code for an existing installation rather than running destructive historical rollbacks.

## Verification and remaining boundaries

The full backend suite passes (198 tests, 1,196 assertions). The final focused readiness/guard/legacy-unit run passes (13 tests, 183 assertions); the MariaDB readiness run passes (6 tests, 164 assertions). All 38 changed PHP files pass syntax checks and targeted Pint; git diff --check passes.

Fresh installation and guarded reset were exercised on an exclusively created local
MariaDB 10.4.32 disposable database, verified before bootstrap and removed afterward.
Readiness tests also run against SQLite memory. They verify seed count/idempotency,
three real logins/permission payloads, dashboard/expenses, POS lookup APIs, retail discounts,
wholesale 3/6 tiers, persisted snapshots, payment/change, completion, stock reconciliation,
negative authorization and safe reset refusals. Connection recording verifies the cashier
workflow uses only its configured ERP database. No RMS/EMS connection is required.

Demo mode omits public registration/email/RMS/bypass/external employee lookup routes and
the scheduled RMS notification job. Normal non-demo password recovery routes remain available;
test/bypass/RMS routes are also absent in production. This does not claim comprehensive
security certification of every unrelated legacy endpoint.

Remaining audit drift is intentionally not copied: inventory_event_types, product_packaging,
unit_conversions, extra obsolete product-unit history columns and orphaned old Unit ledger naming.
The Purchasing route file is absent in the existing project; this milestone repairs its
schema dependency but does not claim the procurement UI/API is demonstrated. Optional legacy
address/email model namespace and employee employment-status field mismatches remain outside
the intended POS path. Unknown legacy Unit types need explicit classification before use.
An already-CLOSED completion replay still returns the existing server error, but creates no
second deduction; the existing POS reconciles order state. These are disclosed rather than
changing business rules to hide them.

The intended Mica demo has **no dependency on current development rows**. Frontend code is
unchanged. Current development schema dumps and unrelated local migration edits are untouched.
No push, merge, deployment, or development-database reset is performed.

See `MICA_DEMO_CHANGED_FILES.md` for the exact changed-file inventory.
