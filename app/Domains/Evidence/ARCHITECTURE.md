# Evidence and simulation foundation

## Audit before implementation (2026-09-09)

Reviewed all Evidence migrations, models, services, requests, resources, controllers/routes, frontend files, existing tests, Rice import adapter/command, reconciliation, and the Sales/Purchasing/Inventory/reference models and services. The containing Git repository reports this entire project as untracked, so there is no clean tracked baseline for a normal diff. Existing source is preserved.

| Current structure | Purpose | Problem / future risk | Decision | Required change |
| --- | --- | --- | --- | --- |
| `evidence_records` / EvidenceRecord | Shared identity, origin, status, method, business/recorded timestamps, raw/context payload, collector | Missing estimated/unknown vocabulary and independent scenario association | GENERALIZE | Nullable scenario FK; extend status vocabulary without rewriting history |
| `supplier_product_observations` / SupplierProductObservation | Supplier commercial terms (the repository name for supplier commercial observations) | Requires real supplier, product UOM and price; cannot represent unidentified leads | KEEP | Keep specialized numeric semantics. External identities remain raw unlinked evidence, never fake suppliers |
| `market_observations` / MarketObservation | Store/channel and selling price | Requires known product UOM; imported package amount must not be mistaken for unit price | KEEP | Preserve raw package context; do not project ambiguous package prices as unit prices |
| `surveys` / Survey | Versioned publication boundary | Current service requires purchase scenarios; this is a purchase-intent research method, not all possible research | KEEP | Document method scope; published structures remain immutable through service |
| `survey_questions`, `survey_question_options` | Questionnaire and option definitions | Local names differ from proposed survey_options; no Rice schema dependency | KEEP | No replacement |
| `survey_respondents` | Research identity/profile | Personal data; not ERP customers | KEEP | Internal only; never substitute for customer master |
| `survey_responses`, `survey_response_answers` | Raw response lineage | Public identifiers are not authenticated respondent identity | KEEP | Retain minimal public receipt and internal raw access |
| `survey_scenarios` / SurveyScenario | Published product/UOM/price stimulus | Cannot identify independent experiments outside surveys | GENERALIZE | Nullable link to reusable EvidenceScenario; preserve existing scenario IDs |
| `simulated_purchase_events` / SimulatedPurchaseEvent | Consumer-stated purchase intent, comparable to a potential sale | Survey-only subtype, currency not snapshotted, missing conversion becomes zero, conflicting submitted UOM ignored | GENERALIZE | Keep table and name; add currency/UOM snapshots, strict checks and analytical projection |
| `evidence_reconciliations` / service/request/resource | Human relationship history | Controller/routes and permission seeds absent; product timeline currently selects direct links only | KEEP | Preserve service and distinctions; document incomplete HTTP wiring, test comparable/exact isolation |
| EvidenceService / SurveyService | Transactional evidence acquisition | No independent scenario or projection; scenario validation gaps | GENERALIZE | Add small scenario service and projection adapter; never call operational posting services |
| Existing controllers/routes/resources/requests | Internal permissions and published public experience | No generic experimental API exists | KEEP | Existing URLs stay; additive response fields and stricter invalid-input rejection only |
| EvidencePermissionSeeder | Existing dotted abilities | Generic already; reconciliation abilities missing | KEEP | No second permission mechanism; no unguarded generic endpoints |
| RiceEvidenceImportService / ImportRiceEvidencePack | Dataset-specific, idempotent import | Rice matching and PHP/kg normalization unsuitable as generic ingestion rules | KEEP | Explicit experiment adapter documentation; no general ingestion framework |
| EvidencePage | Internal collection/timeline | Labels imply all products/evidence are Rice | GENERALIZE | Product-agnostic workspace; Rice retained as named experiment template |
| PublicRiceSurveyPage | Data-driven questionnaire and purchase-intent renderer | Rice name/headings despite generic structure | RENAME | PublicSurveyPage; stable route URL |
| Rice questionnaire builder | Example questionnaire | Embedded in shared workspace | KEEP | Extract under experiments/rice |
| Frontend services/hooks/types | Evidence client | Types omit estimated/unknown and event currency | GENERALIZE | Extend contract; retain existing request shapes |
| Evidence migrations/tests/README | Schema/history and regression evidence | Tests originally apply only acquisition migration; operational sentinels limited | GENERALIZE | Apply additive migration in fixtures and broaden invariants |

No structures are marked REMOVE. No storage concepts need immediate deprecation. Deprecate only the idea that all Evidence or every experimental event is a Rice survey.

## Chosen boundary

ERP owns actual business transactions. Evidence owns observations, reports, assumptions, theory and simulations. An `accounted` epistemic label on evidence never posts an ERP transaction. Origin, epistemic status, collection method, operational lifecycle and scenario identity are different dimensions.

Keep shared metadata in EvidenceRecord, independent experiment identity in EvidenceScenario, and typed payload in specialized observation/event tables. SimulatedPurchaseEvent remains a **consumer purchase-intent subtype**, potentially comparable to a seller's sale, not a simulated supplier procurement document. Future procurement/expense/logistics subtypes can reference EvidenceRecord and use the same projection contract without adding arbitrary columns to this table.

The common `BusinessEventProjection` DTO and `ProjectsBusinessEvent` interface belong to Shared analytics. The first adapter projects survey intent. Operational adapters can later implement the same contract from their own storage. Do not label a draft/confirmed SalesOrder as an actual completed sale or accounting result: Sales has lifecycle states and no explicit currency field. Purchasing has its own currency and posting boundaries; its accounting binding is currently a null implementation. An actual-outcome adapter needs a precise recognized event and amount basis before implementation.

Projection rules: decimal strings or null; original unit identity; explicit currency or null; named amount basis; qualified source/scenario IDs; source record and response lineage. No automatic conversion, aggregation, freight/tax inference or operational writes. A projection is internal data, not a public resource. Missing total amount stays null until a defined calculation exists.

## Historical and unknown data

Raw evidence is never overwritten by normalization. Existing `context_payload` can retain `normalized` data, `value_states` (unknown, not_collected, not_available, not_applicable), external counterparty identity and validity metadata. `derived_payload` remains separate with a method and source references. These conventions do not imply that every old record has those fields. Null means unknown unless an explicit value-state explains why; zero is a known quantity only when actually supplied.

Existing survey scenarios receive independent identities through migration. Existing events inherit that identity and known scenario currency; events without a scenario retain unknown currency. Historical UOM snapshots are not reconstructed from today's mutable product master. New events snapshot original and base units plus the explicit product-unit conversion. Legacy events remain readable with their original IDs and payloads.

New standalone scenarios are supported through the internal service. No scenario-management or universal event HTTP API is added. Public callers must use an active scenario from the published survey, cannot choose arbitrary ERP products, and receive only their minimal submission receipt. Generic scenario metadata and internal analytical projections are never exposed by public survey resources.

## Deferred work

Reconciliation HTTP/UI wiring described as missing in the historical audit above
is now complete using the existing schema and permissions architecture. See
[RECONCILIATION_REPORT.md](RECONCILIATION_REPORT.md). Exact and comparable product
evidence remain separate; this does not change the simulation architecture.

Future work can add one concrete non-survey experimental subtype when a real workflow requires it and define an operational outcome adapter with lifecycle/currency/amount semantics. A generic import interface can wait for a second real adapter. Baseline ERP migrations, imported package-price semantics, respondent identity verification and pagination of general product lookups remain separate work. Candidate searches in reconciliation are already paginated.

Do not implement a Decision Engine, forecasts, trust scores, fuzzy matching, accounting, scraping, workflow engine, complete Purchasing UI or every possible simulation type as part of this foundation.
