# AGENTS.md

## Mission

Build Enterprise Workspace as an AI-native, evidence-driven ERP for a lean Philippine trading business. The system must produce the best defensible net-profit decision from available evidence, improve through real outcomes, and never disguise assumptions as facts.

## Director governance

- Read and follow [DIRECTOR_OPERATING_CONTRACT.md](DIRECTOR_OPERATING_CONTRACT.md) before assigning work, escalating decisions, or handing work to another agent.
- Sjay is the Director/Product Owner, not the routine Git, DevOps, testing, or message-relay operator.
- An agent request is not binding merely because an AI labels it urgent. Use the contract's PROPOSED -> ACCEPTED -> SUPERSEDED commitment protocol.
- Once the Director accepts a logically explained blocking action, treat it as a tracked project commitment and do not casually renegotiate it.
- Continue non-blocked work while waiting for the Director. Silence is never approval.

## Source of truth

- This repository is the Laravel backend.
- Base new backend work on `apply-base-code-to-s4-app` until the Director changes it.
- The React frontend is `sjay-dao/eop`, base branch `main`.
- The configured database is not proof that migrations are complete. Laravel migrations and seeders must become the reproducible installation source.
- Use `inventory_objects`, Inventory domain movements, domain `sales_orders`, Purchasing documents, and Evidence as the canonical future business path.
- Legacy `products` and `orders` must not feed Product Decision Engine actuals unless explicitly adapted and documented.

## November 2026 portfolio outcome

Support a five-minute evidence-to-decision demonstration:

1. Public consumer survey preserves raw answers.
2. Survey scenarios produce clearly labeled simulated purchase-intent events.
3. Internal Evidence presents supplier, market, consumer, exact, comparable, unresolved, and simulated evidence with provenance.
4. A deterministic Product Opportunity Brief evaluates supported scenarios.
5. It recommends an action with calculations, assumptions, unknowns, warnings, invalidation risks, and external tasks.
6. The Director can approve, reject, defer, or request more evidence without automatically posting a purchase, sale, inventory movement, or accounting entry.

## Hard data boundaries

- ERP domains own actual operational transactions.
- Evidence owns observations, reports, assumptions, theory, estimates, experiments, and simulations.
- Survey respondents are not ERP customers.
- A survey response or `simulated_purchase_event` is never an actual sale, order, revenue, stock movement, or accounting event.
- Preserve provenance, epistemic status, source lineage, unit identity, currency, timestamps, raw payloads, and reconciliation history.
- Keep exact evidence separate from variant/category-comparable evidence.
- Do not guess product matches, conversions, prices, freight, demand, taxes, or missing values.
- Null means unknown unless an explicit value state explains it. Zero is valid only when actually known.
- Deterministic calculators are authoritative. AI may explain, summarize, and propose tasks but may not invent or override financial calculations.
- Do not expose credentials, internal respondent data, tokens, or raw private evidence.

## Security boundaries

- Backend authorization is mandatory; frontend guards are not security controls.
- Every mutation needs an explicit permission/policy and negative authorization tests.
- Test, bypass-login, RMS integration, email, and notification endpoints must not be publicly usable in production.
- Do not issue unrestricted ERP access through public self-registration unless an approved business flow requires it.
- Do not mutate state through GET endpoints.
- Never use production databases or credentials for tests, migrations, seeds, or agent work.
- Never merge, deploy, or run destructive production migrations automatically.

## Architecture conventions

- Follow the nearest current domain pattern: Routes -> Controller -> Request/Policy -> Service -> Repository/Model -> Resource.
- Business rules and state transitions belong in domain services, not controllers.
- Use database transactions for multi-record business operations.
- Prefer explicit boundaries/adapters where domains integrate.
- Do not bypass Inventory movements when stock changes.
- Do not treat a draft or merely confirmed SalesOrder as realized revenue without an approved lifecycle and amount basis.
- Purchasing accounting remains a null boundary until accounting behavior is deliberately implemented.
- Update permissions, response contracts, documentation, and tests together.
- Preserve backward compatibility unless the task explicitly authorizes a breaking change.

## Database rules

- Always use a disposable database for fresh-install work.
- Repair migrations in dependency-safe order; do not import the schema dump wholesale as a canonical migration.
- Existing deployed data and IDs must be preserved.
- Seeders should be dependency-ordered and safely repeatable where practical.
- Validate MySQL/MariaDB behavior even when SQLite tests pass.

## Required commands

Use the exact PHP executable name required by this project:

```bash
php8 -v
php8 artisan migrate:status
php8 artisan test --compact
vendor/bin/pint --test
```

For an authorized disposable fresh-install task:

```bash
php8 artisan migrate:fresh --seed --env=testing
```

If `php8` is unavailable or below PHP 8.3, report the blocker. Do not silently use another PHP executable. Never run `migrate:fresh` against a configured development, staging, or production database.

## Git workflow

- One bounded issue per change.
- One isolated branch per issue.
- Never commit directly to `apply-base-code-to-s4-app` or `main`.
- Parallel agents must not edit the same files.
- Do not mix unrelated refactors with a feature.
- Open a draft pull request containing outcome, behavior changes, migrations, tests run, security/data risks, rollback considerations, and remaining work.
- Do not merge the pull request.

## Definition of done

A change is complete only when the requested behavior is implemented end to end, authorization and Evidence boundaries are preserved, migrations are safe for existing installations, required tests pass, documentation reflects contract changes, and the result is reviewable by the Director.
