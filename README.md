# ZenithSoles Affiliates

ZenithSoles Affiliates is a **consumer product-comparison and affiliate-referral platform**. Users search for products across approved merchant or affiliate sources, compare source-reported offers, review timestamped price observations where permitted, and follow tracked links to complete purchases on the external merchant site. ZenithSoles does not currently act as the seller, checkout provider, fulfilment provider, or merchant of record.

The repository is being implemented through an evidence-gated plan. External partner APIs, feeds, permissions, settlement terms, and production approvals are not assumed. Local fixtures and source-agnostic services may be built before those inputs arrive, but fixture tests do not prove partner or production capability.

## Current project status

| Area | Current status |
|---|---|
| Framework | Laravel 12 with PHP ^8.2 and PHPUnit ^11 |
| Local verification | Passing; current suite is 15 tests and 61 assertions without a local coverage driver |
| Composer security audit | 0 advisories in the latest verified run |
| API-independent catalog foundation | Implemented locally: offer snapshots, price history, and explicit ranking primitives |
| Amazon integration | Creators API onboarding and terms verification required; PA-API 5 must not be used for new work |
| Flipkart integration | Official Product, Offer, and Report API route documented; account and contract evidence still required |
| Other merchant programs | Prospect or intermediary route only until current access and terms are evidenced |
| Phase 1 commercial gate | Blocked: 39 required fields and 4 owner sign-offs pending |
| Production release | Not approved |

The authoritative status is maintained in `STAGING_READINESS_REPORT.md`, `STAGING_BLOCKER_REGISTER.md`, `audit/phase1-gate.json`, and `audit/phase3-foundation.json`.

## Product direction

The owner-confirmed direction is **consumer affiliate comparison with post-confirmation reward points**. Users are redirected to external merchants through approved tracked links. A reward is not immediately guaranteed: the affiliate event remains pending until the partner or network confirms the transaction and the commission is received or reconciled under the applicable contract.

The current owner-stated allocation direction is **40% of received affiliate commission to customer points, 40% to the owner/business share, and 20% to a platform scaling and maintenance reserve**. The current points direction is **10 points = ₹1** for voucher redemption, subject to formal policy approval, partner settlement rules, reversal handling, accounting treatment, and voucher-provider approval. These rules must not be presented as a production financial policy until the required owner and finance approvals are recorded.

The product may later include first-party products, but that is future scope and is not part of the current external-affiliate pilot baseline.

## API-pending development policy

Development does not need to stop while partner APIs are pending. The repository may safely build and test:

- source-agnostic product, merchant-offer, and price-snapshot records;
- deterministic ranking primitives that accept explicit caller-supplied weights;
- search, comparison, and history presentation using clearly labelled local fixtures;
- adapter interfaces and sanitized contract fixtures;
- delayed reward states, reversal ledgers, voucher abstractions, and reconciliation workflows behind approval gates;
- security, idempotency, rate-limit, logging, backup, rollback, and release controls.

The repository must not fabricate live prices, stock, ratings, commissions, partner approvals, API credentials, settlement windows, or search-demand rankings. Public webpages are not automatically permission to scrape, bulk-copy, store price history, or rank merchant offers. The active partner research record is `audit/phase1-partner-research-2026-08-24.md`.

## Ranking and data principles

“Top 100 products” remains an owner direction, not a completed data contract. The owner must define the source, geography, category scope, reference period, refresh frequency, deduplication rule, and permission basis. Raw offers from different merchants must not be compared unless product identity, variant, currency, timestamp, and relevant source fields are comparable.

Referral margin may be an explicit and disclosed ranking feature if the owner approves it. The platform must not describe an offer as the cheapest while silently placing it above a cheaper offer for commercial reasons. Missing shipping, tax, coupon, seller, stock, or rating fields must be labelled as unavailable rather than treated as zero or as a favourable default.

## Core implementation areas

| Area | Scope |
|---|---|
| Product comparison | Product records, merchant offers, source timestamps, availability, and price history where permitted |
| Affiliate attribution | Tracked external links, click records, partner events, SubID or equivalent attribution where supported, and reconciliation |
| Reward ledger | Pending, confirmed, reversed, and usable states; idempotent ledger operations; voucher redemption only after policy approval |
| Administration | Product, program, link, commission, user, points, payout, and operational management already present in the Laravel application |
| Security | HMAC partner mutation verification, ownership checks, throttles, correlation logging, security headers, and release gates |
| Operations | Staging-only certification, backups, rollback, secrets, monitoring, incident response, and blocker evidence |

## Local setup

Serve the Laravel `public/` directory as the web root. Do not expose the repository root. Configure `.env` from a secure local or staging secret store; never commit credentials. Use PHP 8.2 or newer within the Laravel-supported range and a database supported by the current release runbook.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

For local verification, use the repository’s testing environment and disposable fixtures. If tracked Laravel bootstrap cache files contain deployment-specific paths, clear them before local Artisan or PHPUnit commands and restore them afterward as described in the release runbook.

## Validation commands

```bash
composer validate --strict
composer audit
python3 tools/validate_release_contracts.py
python3 tools/validate_pilot_decision_inputs.py
python3 tools/validate_pilot_decision_inputs.py --require-approved
php artisan migrate:fresh --force
./vendor/bin/phpunit --configuration phpunit.xml.dist --no-coverage
```

The strict pilot command is expected to fail while required owner inputs and sign-offs are incomplete. This is an intentional safety gate, not a suppressed CI failure.

## Current documentation

| Document | Purpose |
|---|---|
| `DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md` | Evidence-gated implementation plan and anti-hallucination rules |
| `CONCEPT_VIABILITY_ASSESSMENT.md` | Business viability analysis and narrow-pilot rationale |
| `END_TO_END_GAP_ASSESSMENT.md` | Repository, product, business, and operations gap assessment |
| `audit/phase1-partner-research-2026-08-24.md` | Official-source partner/API research and integration boundaries |
| `docs/PILOT_DECISION_INPUT_TEMPLATE.md` | Owner inputs required before the commercial pilot gate can close |
| `docs/PHASE1_REMAINING_DECISIONS.md` | Structured unresolved Phase 1 decisions |
| `docs/PHASE1_OWNER_AND_TIMELINE_PROPOSAL.md` | Proposed roles and dates, explicitly not approvals |
| `docs/API_INDEPENDENT_CATALOG_FOUNDATION.md` | Snapshot and deterministic ranking foundation contract |
| `audit/phase1-gate.json` | Machine-readable Phase 1 blocked-gate evidence |
| `audit/phase3-foundation.json` | Machine-readable Phase 3 implementation evidence |
| `API_SECURITY_CONTRACT.md` | HMAC authentication, mutation, and security contract |
| `PARTNER_INTEGRATION_CONTRACT.md` | Partner payload, idempotency, retries, throttles, and reconciliation contract |
| `docs/RELEASE_OPERATIONS_RUNBOOK.md` | Deployment, migration, rollback, secrets, and operational procedures |
| `docs/STAGING_ACCEPTANCE_RECORD.md` | Staging acceptance and release sign-off template |
| `FINAL_STAGING_HANDOFF_CHECKLIST.md` | Final staging gates and handoff checklist |
| `STAGING_BLOCKER_REGISTER.md` | External staging blocker register and stop conditions |
| `STAGING_READINESS_REPORT.md` | Current local/staging readiness narrative |
| `docs/CONTROL_EXECUTION_MATRIX.md` | Credential-free local controls versus staging-only controls |
| `docs/STAGING_OWNER_EXECUTION_GUIDE.md` | Staging certification procedures |
| `docs/architecture.md` | Application architecture |
| `docs/openapi.yaml` | Implemented API contract |
| `docs/adr/0001-atomic-financial-transitions.md` | Financial state-transition decision record |
| `docs/archive/README.md` | Archived document policy and legacy-file index |

## Release boundary

Local code-level checks passing does not authorize staging or production. Partner certification, API credentials, data-permission review, MySQL migration rehearsal, backup and restore evidence, secret validation, payout reconciliation, fraud controls, observability, capacity evidence, privacy/licensing review, named owners, and explicit staging sign-offs are still required. No real funds, production credentials, or production mutations should be used for certification.

## License

The repository uses the proprietary license metadata recorded in `docs/LICENSE_DECISION_RECORD.md`. External distribution and commercial use remain subject to the repository owner’s legal decision and applicable partner terms.
