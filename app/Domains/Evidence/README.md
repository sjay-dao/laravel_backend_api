# Business Evidence and Simulation

Evidence is the ERP's observational, reported, experimental, theoretical and
simulated business-information layer. Operational transactions remain owned by
Sales, Purchasing, Inventory, Payroll and the other ERP domains. Rice is
Experiment / Research Case #001, not the identity of this domain.

See [ARCHITECTURE.md](ARCHITECTURE.md) for the structure-by-structure audit,
analytical contract, migration semantics and deferred work.

This domain records historical evidence against the existing `inventory_objects`
product master. It does not create a second product, supplier, customer, sales,
or inventory database.

## Evidence semantics

- Supplier commercial observations are provenance-rich `evidence_records` with
  `source_type = supplier`; their normal status is `reported`.
- Market observations are provenance-rich `evidence_records` with
  `source_type = market`; their normal status is `observed`.
- Public survey submissions preserve `survey_responses` and
  `survey_response_answers` before a separate `simulated_purchase_events` row
  is created.
- A simulated event is permanently written as
  `source_type = consumer_survey` and `epistemic_status = simulated`.
- Survey submission does not create a `sales_orders`, legacy `orders`, revenue,
  or `inventory_movements` row.

Supported epistemic statuses are `observed`, `reported`, `accounted`,
`simulated`, `theoretical`, `assumed`, `derived`, `estimated`, and `unknown`.

## Simulation foundation

`evidence_scenarios` provides independent scenario identity. Survey scenarios
link to it without changing their public IDs. Evidence records may reference
the same identity, and future typed experimental subtypes can reuse this
metadata without writing to ERP transaction tables.

`simulated_purchase_events` remains the consumer purchase-intent subtype. New
events snapshot currency and unit identity; unknown currency/conversions stay
null. Invalid or conflicting units, currencies, prices, inactive scenarios and
cross-survey scenarios are rejected. Public simulation submissions must select
an active scenario belonging to their published survey.

The Shared `BusinessEventProjection` / `ProjectsBusinessEvent` contract is
read-only. `SimulatedPurchaseProjection::forScenario()` requires an explicit
scenario and returns internal projections with lineage. No analytics endpoint,
generic event table, operational write path or Decision Engine is introduced.

Apply the additive migration after the existing Evidence migrations:

```bash
php8 artisan migrate --path=database/migrations/2026_09_09_090000_add_evidence_simulation_foundation.php
```

Backfill links existing survey scenarios/events and copies known scenario
currency. It preserves IDs and raw payloads. Legacy events without a scenario
retain unknown currency; historical unit snapshots stay null because current
master data cannot prove historical labels. Rolling this migration back removes
the new metadata only, including standalone scenario identities.

## API

Internal routes require Sanctum authentication and the corresponding Evidence
permission:

- `GET|POST /api/evidence/supplier-observations`
- `GET|POST /api/evidence/market-observations`
- `GET|POST /api/evidence/surveys`
- `GET|PATCH /api/evidence/surveys/{survey}`
- `POST /api/evidence/surveys/{survey}/publish`
- `GET|POST /api/evidence/surveys/{survey}/responses`
- `GET /api/evidence/inventory-objects/{inventoryObject}/evidence`
- `GET /api/evidence/reconciliations?scope=unresolved&page=1&per_page=20`
- `GET /api/evidence/reconciliations/{evidenceRecord}`
- `GET /api/evidence/reconciliations/{evidenceRecord}/candidates?search=...&page=1&per_page=20`
- `POST /api/evidence/reconciliations/{evidenceRecord}`

Public respondents are restricted to published survey read/submit routes:

- `GET /api/evidence/public/surveys/{survey:code}`
- `POST /api/evidence/public/surveys/{survey:code}/responses`

The public submit response is intentionally a minimal receipt; respondent
profile data and raw answers remain available only through internal evidence
permissions.

The product evidence endpoint returns separate supplier, market, raw consumer
response, and simulated-purchase groups so raw evidence and derived behavior
remain visibly distinct.

It also returns `exact_evidence` (direct records plus current reviewed exact
links) and `comparable_evidence` (current variant/category links). The original
direct collections remain compatible. Unresolved, rejected and superseded
relationships do not enter either reviewed product group.

## Human reconciliation

The existing table and service now have authenticated HTTP/UI wiring; no schema
change was needed. Queue scopes are `unresolved` (default), `rejected` and `all`.
Both queue and candidate searches are paginated (maximum 100 records per page).
Candidate search covers code, name, brand, variant, packaging and specification.

`evidence.reconciliations.view` permits queue/detail/history/candidate reads;
`evidence.reconciliations.create` permits decision creation and revisions.
The latter reuses the existing FormRequest permission name. Neither permission
grants public survey access to internal evidence. Both are available from the
existing Evidence seeder and granted to the existing admin role.

The Reconciliation section in `/evidence` accepts an explicit relationship,
optional product UOM, and required notes. Exact, variant and category-comparable
require a product; unresolved/rejected omit it. Product UOM must belong to the
selected product and never converts the source observation. No product is
preselected. Use All or Rejected to inspect history or revise a previous review.

Only unlinked external market/supplier/government/competitor records enter this
workflow. Survey identities and simulated events are excluded even for an
authorized reviewer. Each decision creates a new audit row and supersedes the
previous row inside a transaction locked on the source record; source fields
and operational data remain unchanged. Imported unmatched records appear
without running the importer again.

Source summaries display supplied original and normalized values independently.
Legacy import PHP/kg fields remain explicitly labeled; generic normalization
can use `context_payload.normalized.unit_amount`, `currency_code` and `unit_code`.
Unknown data remains unknown. Detail includes the raw external payload/context
without loading respondent profiles or answers.

See [RECONCILIATION_REPORT.md](RECONCILIATION_REPORT.md) for tests and limitations.

## Permissions

`EvidencePermissionSeeder` provides the `evidence.*` permissions and grants
them to the existing `admin` role. Other internal roles should receive only the
actions they require through the existing role-permission administration UI.

Run the seed idempotently with:

```bash
php8 artisan db:seed --class=EvidencePermissionSeeder --force
```

## Workspace and experiment UI

The authenticated `/evidence` workspace supports product-agnostic supplier and
market collection, product evidence, and a general purchase-intent survey
template. The Rice template lives under `experiments/rice` and remains available
as Case #001. `PublicSurveyPage` renders published purchase-intent surveys at
the unchanged `/evidence/surveys/{code}` URL; the old component name is a
compatibility export. Raw respondent information remains internal.

## Starter Rice evidence pack

`RiceEvidenceImportService` and `ImportRiceEvidencePack` are dataset-specific
adapters. Their Rice matching and PHP/kg source conventions must not become
generic ingestion behavior. Their command signature and import keys remain
unchanged.

The one-off importer intentionally writes only Evidence-domain rows. It accepts
the JSON and CSV source representations together, deduplicates them with a
stable source key, and never creates a supplier, supplier commercial
observation, purchase order, sales order, accounting entry, inventory movement,
survey respondent, or simulated purchase.

```bash
php8 artisan evidence:import-rice-pack --seed-json="C:\\path\\to\\rice_evidence_seed.json" --market-csv="C:\\path\\to\\market_observations.csv" --supplier-leads-csv="C:\\path\\to\\supplier_leads.csv"
```

Use `--dry-run` to view product and supplier reconciliation outcomes without
writing. A public market record with no exact `InventoryObject` and compatible
object UOM is stored as unlinked `evidence_records` evidence with its raw
description, URL, source status, dates, pricing, and reconciliation candidate
in its provenance payload. It is not guessed onto a near-match product.
