# Evidence architecture correction: implementation report

Completed 2026-09-09. Detailed pre-implementation audit: [ARCHITECTURE.md](ARCHITECTURE.md).

This report describes the simulation-foundation task. Its reconciliation HTTP/UI
limitation was subsequently resolved; see [RECONCILIATION_REPORT.md](RECONCILIATION_REPORT.md)
for the current reconciliation behavior and validation.

## 1. Audit findings

The shared metadata plus specialized payload design was already sound. The main drift was in the UI/template identity, survey-bound scenario identity, and incomplete simulation context. No universal event table or operational transaction duplication was necessary. Reconciliation services exist but their HTTP wiring is incomplete.

## 2. What was wrong

The workspace and public renderer described all evidence/products as Rice. Simulated events did not snapshot currency or historical unit identity. Missing conversion factors could become zero; submitted units could be silently overridden by a scenario; inactive or conflicting stimuli could be accepted. There was no reusable scenario identity or common analytical contract, and estimated/unknown were absent from the status vocabulary.

## 3. What was already correct

Provenance, separate origin/status/method, business and recorded timestamps, raw response/answer preservation, append-only observation services, specialized commercial amount fields, nullable unknown commercial values, operational isolation, existing authorization, minimal public receipts, reconciliation history, and idempotent Rice import keys were retained.

## 4. Tables retained unchanged

`supplier_product_observations`, `market_observations`, `surveys`, `survey_questions`, `survey_question_options`, `survey_respondents`, `survey_responses`, `survey_response_answers`, and `evidence_reconciliations`. All operational ERP tables and posting services are unchanged.

## 5. Tables generalized

`evidence_records` and `survey_scenarios` can reference an independent EvidenceScenario. `simulated_purchase_events` remains the existing consumer purchase-intent subtype and now carries historical money/unit context. Original IDs, table names, raw payloads and research relationships are retained.

## 6. New schema

- `evidence_scenarios`: identity, unique code, name, description, context, creator and timestamps.
- Nullable `evidence_scenario_id` FK on `evidence_records` and `survey_scenarios`.
- Nullable `currency_code` and JSON `unit_snapshot` on `simulated_purchase_events`.

No universal event/EAV table, alternate supplier/customer master, or operational transaction table was added.

## 7. Deprecations

No database concept was removed or deprecated. `PublicRiceSurveyPage` is retained as a compatibility export of the generic renderer. Rice remains a selectable Case #001 template and its existing import command/service are unchanged dataset-specific adapters.

## 8. Backend files changed or added

Paths relative to `backend/`:

```text
app/Domains/Evidence/Models/EvidenceRecord.php
app/Domains/Evidence/Models/SurveyScenario.php
app/Domains/Evidence/Models/SimulatedPurchaseEvent.php
app/Domains/Evidence/Models/EvidenceScenario.php                         (new)
app/Domains/Evidence/Services/EvidenceService.php
app/Domains/Evidence/Services/SurveyService.php
app/Domains/Evidence/Services/EvidenceScenarioService.php                (new)
app/Domains/Evidence/Services/SimulatedPurchaseProjection.php            (new)
app/Domains/Evidence/Requests/SubmitSurveyResponseRequest.php
app/Domains/Evidence/Resources/EvidenceRecordResource.php
app/Domains/Evidence/Resources/SimulatedPurchaseEventResource.php
app/Domains/Evidence/Resources/PublicSurveyResource.php
app/Domains/Evidence/Resources/PublicSurveyResponseResource.php
app/Domains/Shared/Analytics/BusinessEventProjection.php                 (new)
app/Domains/Shared/Analytics/ProjectsBusinessEvent.php                    (new)
database/migrations/2026_09_09_090000_add_evidence_simulation_foundation.php (new)
tests/Feature/EvidenceAcquisitionTest.php
app/Domains/Evidence/README.md
app/Domains/Evidence/ARCHITECTURE.md                                     (new)
app/Domains/Evidence/IMPLEMENTATION_REPORT.md                            (new)
```

The root `project_context.md` also documents the resulting architecture and consistently uses `php8 artisan`. Composer wrapper commands that could invoke another binary are no longer recommended for Artisan work.

## 9. Frontend files changed or added

Paths relative to `frontend/`:

```text
src/modules/evidence/pages/EvidencePage.tsx
src/modules/evidence/pages/PublicRiceSurveyPage.tsx                     (compatibility export)
src/modules/evidence/pages/PublicSurveyPage.tsx                         (new generic renderer)
src/modules/evidence/types/evidence.ts
src/modules/evidence/experiments/rice/surveyTemplate.ts                  (extracted existing template)
src/modules/evidence/templates/purchaseIntent.ts                        (new general method template)
src/app/router/public.routes.tsx
```

The workspace offers general product purchase-intent surveys and the Rice experiment. Supplier/market forms preserve their entered values as raw payload. Event prices show captured currency or explicitly unknown currency. Zero lead time is displayed as zero days instead of being hidden as missing.

## 10. APIs affected

All existing URLs are retained; no new scenario/analytics/public management endpoint was introduced.

- Supplier and market observation POST validation additionally accepts `estimated` and `unknown` through the existing status list.
- Survey create/update internally creates independent scenario metadata for new stimuli.
- Simulation submission accepts optional `currency_code`. Conflicting scenario UOM, price or currency, inactive/cross-survey scenarios, and nonpositive conversion factors are rejected. Null conversion remains unknown. Public simulation submission now requires a scenario from its published survey; assisted internal legacy unscoped submissions remain supported.
- Internal EvidenceRecord responses add `evidence_scenario_id`; internal simulated event responses add `currency_code` and `unit_snapshot`.
- Public survey reads contain active stimuli only. The minimal submission receipt additionally includes currency; it does not expose raw answers, respondent profiles, generic scenario metadata or analytical projections.

## 11. Migration behavior

The additive migration creates independent identities for existing survey scenarios, links their evidence events and copies known scenario currency. It leaves legacy unscoped currency and historical unit snapshots null. Existing IDs/raw payloads remain unchanged. Rollback removes only the new columns/table, including new independent scenario metadata.

Migration up/down/backfill were exercised in isolated SQLite tests. On 2026-09-09, the pending reconciliation migration and simulation foundation migration were applied successfully to the configured application database as batch 30 using `php8 artisan migrate` with explicit paths. Migration status confirms both ran. The database had no survey scenarios to backfill; consistency queries found no missing scenario identities or currencies on scenario-linked events. Backfill with existing scenario data is covered by the automated tests. Older pending Inventory/Supplier/Unit migration entries were left untouched because they require a separate baseline-schema review.

## 12. Validation results

| Check | Result |
| --- | --- |
| Baseline `php8 artisan test --compact` | Passed: 9 tests, 69 assertions |
| Focused `php8 artisan test --compact --filter=Evidence` | Passed: 14 tests, 132 assertions at that implementation stage |
| Final `php8 artisan test --compact` | Passed: 16 tests, 140 assertions |
| Final `npm.cmd run build` | Passed; existing large-bundle warning remains |
| `npm.cmd run lint -- src/modules/evidence src/app/router/public.routes.tsx` | Passed |
| Repository-wide `npm.cmd run lint` | Failed on 9 existing hook-naming errors in Reference branch/warehouse components/hooks; unrelated warnings also present. Evidence's memo warning was subsequently fixed and scoped lint passed. |
| Pint on changed/new PHP files | Completed successfully |

Tests cover historical/raw evidence, all nine epistemic statuses, unknown values, non-Rice acquisition, separate scenario projections, conflicting units/prices/currencies, explicit BOX-to-base conversion, snapshot preservation after master edits, invalid/null conversion, public privacy, category-comparable reconciliation history, migration backfill/rollback and existing import idempotency. Seeded sentinel rows remain unchanged across sales, orders, purchasing, receipts, invoices, payments, inventory, cash and accounting tables. Sentinels verify write isolation; they do not represent full integration tests of every ERP schema.

After application-database migration, `php8 artisan test --compact` passed again with 16 tests and 140 assertions. Application-database validation used migration status and read-only consistency queries; no browser interaction was used. Git reports the project as untracked within its containing repository, so no clean tracked baseline diff was available.

## 13. Remaining architectural risks

- Reconciliation controller/routes/permission seeds remain absent; direct product timelines do not yet present comparable reviewed evidence.
- ERP baseline migrations are incomplete; production-engine migration validation is separate from SQLite tests.
- Legacy scenario-less events retain unknown scenario/currency, and old UOM snapshots cannot be recovered reliably.
- Import package totals and normalized per-kg figures must not be conflated with unit prices; the new projection deliberately covers only simulated purchase intent.
- Scenario services and projections are internal foundations with no management UI/API yet. Future subtypes need concrete validation and authorization.
- Sales operational outcome/currency semantics must be defined before introducing an actual-sale adapter. No actual-outcome adapter is claimed here.
- Existing respondent references are not verified identities. Raw profiles remain private, but identity validation and client-supplied derived-data provenance need a separate research-method review.
- Observation immutability is enforced by acquisition workflow, not a database-wide prohibition on administrative updates. No currency conversion, trust scoring or analytical aggregation is implied.

## 14. Next implementation

Complete reconciliation HTTP/UI wiring using existing permissions and preserve exact/comparable groups. Then implement one real non-survey experimental use case with a typed payload and scenario identity. Define one operational outcome projection with explicit lifecycle, unit, currency and amount basis before any expected/actual comparison.

## 15. Explicitly not next

Do not proceed directly to a Decision Engine, forecasting/AI pricing, trust scores, fuzzy reconciliation, accounting engine, universal event storage, broad ingestion/scraping framework, complete Purchasing frontend, or all simulated event types. These require validated business workflows and analytical semantics first.
