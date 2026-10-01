# Per-line sales pricing contract

Create/update accepts optional items[].sale_type: RETAIL or WHOLESALE. Omitted/null modes inherit the existing order.sale_type default. Each line calls the existing SalesPriceResolver with its effective mode and quantity. Discounts, cents snapshots, totals, payments and fulfillment use their existing implementations.

sales_order_items.sale_type is an additive nullable historical snapshot. Existing rows keep their prices and IDs; resources expose null legacy modes using the saved order default. Migration does not reprice or backfill. Rollback removes only the new column; this loses new line-mode metadata but preserves saved prices.

Store/update FormRequests enforce existing sales.sales.create/sales.sales.update permissions; no Admin bypass or new role is introduced. API clients that omit line modes retain order-wide pricing.

Verified: mixed lines, wholesale tier changes, invalid-mode rollback, inherited defaults, historical snapshots after price configuration changes, permission allow/deny, existing payment/completion/receiving and financial/dashboard regressions. Disposable MySQL migration up/down preserves existing rows.
