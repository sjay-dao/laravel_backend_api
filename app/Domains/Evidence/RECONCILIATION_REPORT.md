# Human Evidence reconciliation — implementation report

Completed 2026-09-09. This task wires the existing reconciliation design into
HTTP and the authenticated Evidence workspace. It does not redesign Evidence
or add simulated business event types.

## 1. Existing structures

Reused `evidence_reconciliations`, EvidenceReconciliation, the reconciliation
FormRequest/resource/service, and EvidenceRecord's current/history relationships.
The existing schema contains nullable product/UOM links, relationship type,
active/superseded status, notes/rationale, reviewer, review time and superseded
time. It already supports `exact`, `variant_match`, `category_comparable`,
`unresolved` and `rejected`. Existing frontend API methods/types were completed.

## 2–3. Schema changes and justification

None. The existing schema represents every required invariant. No new table,
column, migration, data import or operational entity was needed. The existing
reconciliation migration had already run in application-database batch 30.

## 4. Routes

All four routes use `auth:sanctum` under `/api/evidence`:

| Method | Route | Purpose |
| --- | --- | --- |
| GET | `/reconciliations` | Paginated unresolved queue; optional rejected/all scope |
| GET | `/reconciliations/{evidenceRecord}` | External source detail, current decision and review history |
| GET | `/reconciliations/{evidenceRecord}/candidates` | Paginated search of active Inventory Objects and their UOMs |
| POST | `/reconciliations/{evidenceRecord}` | Human decision or revision |

Queue and candidate pagination default to 20 and allow up to 100 rows per page.
Search covers code, name, brand, variant, packaging and specification. Suggested
IDs from import context may be returned but never cause a selection or write.

## 5. Backend changes

Added `Controllers/EvidenceReconciliationController.php` and
`Resources/ReconciliationEvidenceResource.php` in the Evidence domain.
Updated `Services/EvidenceReconciliationService.php`,
`Controllers/ProductEvidenceController.php`, `routes.php`, and
`database/seeders/EvidencePermissionSeeder.php`.

The service now guards all queue/detail/search/write paths to unlinked external
`market`, `supplier`, `government` and `competitor` records. Survey source
identities and records attached to simulated events are excluded. Reconciliation
does not load survey answers/respondents. It also supplies current reviewed
product links for the product endpoint.

Source summaries keep original amount/UOM and supplied normalized amount/UOM
distinct. Legacy PHP/kg import fields retain explicit context; generic supplied
normalization can use `context_payload.normalized.unit_amount`, `currency_code`
and `unit_code`. Unknown fields stay unknown. Raw external evidence and context
remain available in detail, without being rewritten.

## 6. Permissions and application setup

- `evidence.reconciliations.view` protects queue, detail/history and candidate search.
- `evidence.reconciliations.create` reuses the permission name already present
  in StoreEvidenceReconciliationRequest and protects decisions/revisions.
- `evidence.products.view` continues protecting product evidence.

Both reconciliation permissions were added to the existing Evidence seeder,
which retains its existing admin-grant behavior. Ran:

```powershell
php8 artisan db:seed --class=EvidencePermissionSeeder --force --no-interaction
```

Application-database checks confirmed both permissions exist and the admin role
has both. Other internal roles can receive them through existing role/permission
administration. Refresh the app/session to load newly granted permissions.

## 7. Frontend changes

Added `components/ReconciliationPanel.tsx`, `components/EvidenceRecordTable.tsx`
and `utils/displayValue.ts` under `frontend/src/modules/evidence`. Updated
EvidencePage, Evidence API services/hooks/types, the protected route guard and
the sidebar's Evidence entry.

The workspace now provides unresolved/rejected/all queues, explicit record
selection, source inspection, paginated product search, optional UOM selection,
all five relationship types, required notes, save-and-next and history. Product
selection starts empty and clears when the relationship changes. Selected UOM
clears when the product changes. No source amount is converted by choosing a UOM.

Read-only reviewers see history but cannot save. A reviewer with only the
dedicated read permission can enter `/evidence` through the sidebar without
loading unrelated supplier/survey/general Inventory endpoints. Product viewers
retain the existing workspace plus the permission-gated Reconciliation tab.

## 8. Product evidence

Existing `evidence_records` and specialized direct collections are retained.
Two additive collections are rendered in separate UI tables:

- `exact_evidence`: direct product records plus active human-reviewed exact links.
- `comparable_evidence`: active variant/category-comparable links, retaining the
  specific relationship and review metadata.

Unresolved/rejected and superseded decisions are excluded. Revising a link
changes which reviewed group it belongs to without rewriting the source product
ID. Direct source links are outside this unlinked-evidence workflow.

## 9. Audit behavior

The existing transaction locks the EvidenceRecord, supersedes current review
rows and inserts a new review. Prior relationship, product/UOM, reviewer, time
and notes remain available. Invalid combinations fail without superseding the
current review. Exact/variant/category require a product. Unresolved/rejected
omit product and UOM; a supplied UOM must belong to the selected product.

Source URL, description, identity, amounts, normalized context, dates, status,
method, raw payload, import key and source timestamps remain unchanged.

## 10. Imported Rice evidence

The importer and import key semantics are unchanged. Unmatched imported rows
appear directly in the queue and remain unlinked until a human records a
relationship. Re-import is unnecessary; replaying an import in tests preserves
the same EvidenceRecord and existing review. No Rice-like candidate is selected
automatically. No real application-data reconciliation decisions were fabricated.

The configured application database currently reports **6 unresolved external
records** through the reconciliation service.

## 11. Backend validation

| Command | Result |
| --- | --- |
| `php8 artisan test --compact --filter=EvidenceReconciliationTest` | Passed: 8 tests, 121 assertions |
| `php8 artisan test --compact` | Passed: 24 tests, 261 assertions |
| `php8 artisan route:list --path=evidence/reconciliations -v` | All four routes registered with Sanctum authentication |
| Pint on changed/new PHP files | Completed successfully |
| Application permission seed and read-only service checks | Passed; admin has both permissions; 6 unresolved external records |

Added `tests/Feature/EvidenceReconciliationTest.php`. Shared existing fixture
setup was extracted into `tests/Support/BuildsEvidenceTestDatabase.php` and reused
by EvidenceAcquisitionTest; its behavioral tests remain intact. Existing Rice
import and Evidence acquisition tests pass.

Coverage includes imported unresolved visibility, no automatic linking,
exact/comparable/variant decisions, unresolved/rejected exclusions, revision
history, raw-source preservation, invalid combinations, anonymous/unauthorized
access, read-only access, survey-data exclusion, candidate search, pagination,
seed idempotency and import replay after review.

## 12. Frontend validation

`npm.cmd run build` passed. The existing large-bundle warning remains.

```powershell
npm.cmd run lint -- src/modules/evidence src/app/router/protected.routes.tsx src/shared/hooks/useSidebarItems.tsx
```

Scoped lint passed without warnings. Unrelated repository-wide lint errors were
not changed. An interactive browser session was not exercised; validation used
the production build, static lint and authenticated HTTP feature tests.

## 13. Operational side effects

Feature tests compare complete before/after rows in seeded sentinel tables for
sales, legacy orders, purchase orders, goods receipts, supplier invoices,
payments, inventory movements/stocks, cash and accounting. Product, UOM and
supplier master rows are also unchanged. These checks prove the reconciliation
write boundary in the isolated fixture; they are not full integration tests of
every operational schema. Application setup wrote permission metadata only.

## 14. Remaining limitations

- This workflow reviews unlinked external source records, not existing direct
  product links or respondent evidence. Other source types require an explicit
  scope/privacy decision before being added to the allowlist.
- Source summary fields support known import fields and the documented generic
  normalization convention. Unrecognized source formats remain inspectable as
  raw/context JSON rather than being guessed.
- Row locking serializes writes and preserves every revision. It does not reject
  a stale browser decision automatically; concurrent human decisions appear as
  successive revisions in history.
- Candidate search is textual, not fuzzy matching, and exposes active products.
- Tests use isolated SQLite fixtures because the wider ERP schema baseline has
  older migration inconsistencies. No unrelated migrations were applied.
- Product evidence uses existing nonpaginated collections; pagination of very
  large product histories remains future work.

## 15. Recommended next task

Have an authorized product-domain reviewer inspect the six unresolved external
records and validate exact/comparable decisions against their source identity
and UOM. Do not automate those decisions. No Decision Engine, forecasting,
scoring, operational adapter, new simulator, or other follow-on implementation
was started.
