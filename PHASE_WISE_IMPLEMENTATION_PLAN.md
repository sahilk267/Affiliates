# Affiliates — Phase-Wise Implementation Plan

**Repository:** `sahilk267/Affiliates`
**Baseline commit:** `f23a894`
**Audit baseline:** 187 files scanned, 15 recorded findings, release score **49.18/100**, release status **BLOCKED**. Performance and AI Safety were marked **NOT MEASURABLE** because the repository contains no runtime profiling evidence and no AI implementation.[1] [2]

## 1. Implementation Objective

The objective is to move the Affiliates platform from a broad but unreliable Laravel prototype to a system that can be **initialized, authenticated, financially trusted, tested, observed, and released with evidence**. The implementation order is intentionally risk-first: database and security blockers precede feature expansion, and every phase ends with an explicit validation gate.

The plan is organized into eight delivery phases. The first six phases address the audit’s P0 and P1 findings; the final two phases address integration maturity and controlled production rollout. Estimates are engineering effort, not elapsed calendar time, and assume one Laravel/PHP engineer, one reviewer familiar with security and data, and part-time QA/DevOps support.

> **Release policy:** No production deployment should occur until Phase 5’s release gates pass. Phases 6–8 improve operational maturity and integration reliability; they should be completed before treating the platform as enterprise-ready.

## 2. Finding-to-Phase Mapping

| Audit finding | Severity | Phase | Completion condition |
|---|---|---:|---|
| `DB-001` — Migration dependency order | Critical / P0 | 1 | Fresh database can migrate from zero on a clean CI database. |
| `DB-002` — Conversion schema/runtime drift | Critical / P0 | 1 | Canonical conversion contract is shared by migration, model, API, services, and tests. |
| `DB-003` — Commission schema/runtime drift | Critical / P0 | 1 | Commission creation and payout transitions persist successfully against the canonical schema. |
| `AUTH-001` — Session/guard mismatch | High / P0 | 2 | One authentication mechanism works consistently for admin and consumer routes. |
| `SEC-001` — Unauthenticated points credit | Critical / P0 | 2 | Points mutation requires authenticated, authorized, signed service access. |
| `SEC-002` — Unauthenticated conversion reporting/idempotency gap | High / P0 | 2 and 3 | Partner authentication, event uniqueness, replay protection, and transactional processing pass tests. |
| `SEC-003` — Default admin password in source | Critical / P0 | 2 | No fixed production credential remains in source; existing deployments are rotated. |
| `QA-001` — CI suppresses test failures | High / P0 | 5 | CI fails on migration, test, static-analysis, and security-check failures. |
| `QA-002` — No automated tests | High / P0 | 5 | Unit, feature, API, security, and migration tests cover release-critical flows. |
| `BACK-001` — Direct `ApiController` construction | High / P1 | 4 | Admin API test paths use dependency injection or a service boundary and pass feature tests. |
| `OPS-001` — Missing `.env.example` | High / P1 | 6 | A sanitized environment template and configuration validation command exist. |
| `API-002` — Consumer buy flow bypasses click tracking | High / P1 | 3 | Consumer purchases produce a traceable click identifier before redirect. |
| `API-001` — No OpenAPI contract | Medium / P2 | 6 | Versioned API contract is published and checked by contract tests. |
| `BACK-002` — Hard-coded IP geolocation | Medium / P2 | 7 | Geo fields use a documented provider or are explicitly removed from analytics claims. |
| `DOC-001` — Missing referenced documentation | Medium / P2 | 6 | README, architecture, API, environment, and deployment documentation match the code. |

## 3. Phase 0 — Baseline Freeze, Data Safety, and Working Agreements

**Estimated effort:** 4–8 hours
**Depends on:** None
**Primary outcome:** A safe starting point for schema and security changes.

Before changing runtime behavior, create a protected remediation branch and record the current commit, database status, environment variables, deployment configuration, and any existing production data. If a database already exists outside this checkout, take a verified backup and test restoration before running any destructive migration or data transformation.

| Workstream | Implementation tasks | Deliverable |
|---|---|---|
| Repository control | Create a remediation branch from `f23a894`; enable pull-request review and required CI checks. | Protected branch and change log entry. |
| Data safety | Export the current schema and data where applicable; record row counts for users, programs, links, clicks, conversions, commissions, points, referrals, and redemptions. | Versioned baseline snapshot and restore procedure. |
| Configuration inventory | Collect required `APP_KEY`, database, session, mail, storage, and API settings without committing secrets. | Sanitized configuration inventory. |
| Acceptance definitions | Agree on the canonical conversion, commission, points, authentication, and partner API contracts before implementation. | Approved contract checklist. |

**Gate 0:** Backup restoration is verified; the remediation branch is protected; no production secret is copied into the repository.

## 4. Phase 1 — Canonical Database and Financial Data Contracts

**Estimated effort:** 20–40 hours
**Depends on:** Phase 0
**Addresses:** `DB-001`, `DB-002`, `DB-003`
**Primary outcome:** A clean, internally consistent schema that supports the actual runtime flows.

This is the first implementation phase because all conversion, commission, cashback, and referral behavior depends on persistence. Do not patch individual SQL errors in isolation. Define the target schema once, then align migrations, models, controllers, services, seeders, and tests to that contract.

### 4.1 Migration ordering

Reorder or replace the January 2025 product/reward migrations so foundational tables are created before dependent foreign keys. The required dependency order is approximately: `users` and `programs`, then `products`, `links`, `product_links`, `product_commissions`, `clicks`, `conversions`, `commissions`, and finally wallet/referral/cashback/gift/redemption relationships. Self-referential user foreign keys should be added after the `users` table exists.

Create a clean-database CI job that runs migrations from an empty database. The job must fail on any foreign-key or schema error.

### 4.2 Conversion contract

Choose one conversion representation and use it everywhere. The recommended canonical contract retains the fields used by the API and model—`click_id`, `link_id`, `user_id`, `program_id`, `event_type`, `event_data`, `conversion_value`, `currency`, `order_id`, `customer_id`, `product_id`, `product_name`, `quantity`, `commission_amount`, status, sub-affiliate data, and processing timestamps. If legacy columns such as `conversion_id`, `order_value`, `commission_rate`, or `converted_at` are retained for compatibility, document them and populate them consistently rather than keeping parallel meanings.

Use a shared enum or value object for event types so the API validator and database constraint accept exactly the same values. Add a unique partner event identifier and a unique constraint appropriate to the merchant integration.

### 4.3 Commission contract

Choose a single payout schema. The recommended canonical model includes `conversion_id`, `user_id`, `amount`, `commission_type`, `status`, `payout_method`, `payout_details`, `paid_at`, and notes, plus explicit fields for parent/sub-affiliate allocation if that split remains a supported business rule. Rename or migrate legacy `payment_method`, `transaction_id`, and split-amount columns only after mapping existing data.

Implement state transition rules: pending → approved → paid, with cancellation/rejection rules and audit metadata. Prevent invalid transitions and record the acting administrator.

### Phase 1 deliverables

| Deliverable | Validation |
|---|---|
| Reordered/reconciled migrations | `migrate:fresh` succeeds on an empty MySQL test database. |
| Canonical conversion schema | API create, model hydration, and conversion query tests pass. |
| Canonical commission schema | Create, approve, reject/cancel, and paid transition tests pass. |
| Data migration scripts | Existing rows are transformed with row-count and checksum checks. |
| Updated seeders | Seeders contain no production credentials and run on the canonical schema. |

**Gate 1:** A clean database can be created from zero, seeded safely, and used to persist one complete click → conversion → commission record.

## 5. Phase 2 — Authentication, Authorization, and Credential Remediation

**Estimated effort:** 24–48 hours
**Depends on:** Phase 1 schema stability
**Addresses:** `AUTH-001`, `SEC-001`, `SEC-002`, `SEC-003`
**Primary outcome:** One auditable authentication model and protected reward APIs.

Use Laravel’s normal authentication guard as the source of truth. Replace manual session writes in `AuthController::login()` with `Auth::attempt()` or `Auth::login()`, regenerate the session after login, and invalidate/regenerate the session on logout. Keep role authorization in an explicit middleware or policy layer, but do not maintain a second user identity mechanism in parallel.

Protect consumer routes with the same guard used by `@auth` and `auth()->user()`. Protect admin routes using a role middleware or policy built on the authenticated user. Add tests for login, logout, inactive users, session fixation resistance, unauthorized consumer access, unauthorized admin access, and authorized admin access.

Move reward mutation APIs behind an explicit partner-authentication boundary. A practical first version is a server-to-server API key or HMAC signature per affiliate partner, stored only in environment/secret storage and associated with a `Program`. Every points credit and conversion report must identify the partner, verify the signature, validate timestamp/skew, and reject replays.

Remove the fixed administrator password from `AdminUserSeeder`. Use an environment-provided bootstrap secret only for local setup, or require an interactive first-admin creation command. Rotate any existing deployed credentials immediately.

**Gate 2:** An unauthenticated request cannot credit points or report a conversion; an authenticated consumer can access the dashboard/wallet/referrals; a non-admin cannot access admin routes; replayed partner events are rejected.

## 6. Phase 3 — Transactional Attribution and Reward Integrity

**Estimated effort:** 32–64 hours
**Depends on:** Phases 1 and 2
**Addresses:** `SEC-002`, `API-002`, conversion integrity risks
**Primary outcome:** End-to-end attribution with exactly-once financial effects.

Create a single application service for the conversion pipeline, for example `ConversionProcessingService`, with a transaction boundary around conversion creation, click state update, link counters, commission creation, cashback points, and referral points. Use row locks or an equivalent concurrency control when claiming a click so two concurrent requests cannot both convert it.

Use `CommissionService` as the single commission calculation and persistence path. Remove the controller-local commission calculator or make it a thin delegate. Product-specific commission overrides, program-level fallback rates, sub-affiliate allocation, and cashback calculations must have one documented source of truth.

Fix the consumer purchase flow. `ProductController::buy()` should either call a tracked-click service before redirecting or redirect through a dedicated tracking endpoint that creates the click and signs the downstream identifier. Define how the merchant callback or conversion report carries the click identifier and partner event ID.

Add idempotency at the database boundary. A unique merchant event ID should cause a retry to return the original processing result rather than create another conversion, commission, or points credit. Points transactions should also use a uniqueness rule on the business reference where appropriate.

Repair `AdminController` API test helpers by injecting the API controller or, preferably, invoking the same application services used by production endpoints. The test UI must not instantiate a dependency-injected controller with missing constructor arguments.

**Gate 3:** A successful consumer purchase produces one click, one conversion, one commission allocation, one cashback effect, and the expected referral effect. Repeating the same partner event produces no additional financial effect.

## 7. Phase 4 — Backend Consolidation, Admin Integrity, and Analytics Correctness

**Estimated effort:** 24–48 hours
**Depends on:** Phases 1–3
**Addresses:** `BACK-001`, maintainability concerns, analytics reliability
**Primary outcome:** Reduced duplication and consistent business behavior.

Move repeated Eloquent queries and business rules out of `AdminController`, `ProductController`, and `ApiController` into focused application services. Introduce repositories only where they create a meaningful boundary—particularly for conversion, commission, points ledger, and partner integration persistence—not as a mechanical wrapper around every model.

Consolidate duplicated product commission CRUD. Select one controller as the route owner and have it call a product commission service. Add authorization checks to ensure product/program relationships are valid and imports cannot create cross-tenant or invalid records.

Replace hard-coded IP geo results in `ApiController` with a documented GeoIP provider or remove the fields from reports until a provider is available. Cache lookups, avoid blocking the conversion request where possible, and document retention/privacy behavior.

Add structured audit events for admin actions, reward mutations, approval/rejection/payment transitions, and partner API failures. Avoid logging secrets, raw credentials, or excessive personal data.

**Gate 4:** The admin UI, API, and consumer flows call shared services; no direct controller construction remains; analytics fields are either accurate or explicitly labeled unavailable; admin state changes are auditable.

## 8. Phase 5 — Automated Tests, CI Enforcement, and API Contracts

**Estimated effort:** 48–96 hours
**Depends on:** Phases 1–4
**Addresses:** `QA-001`, `QA-002`, `API-001`
**Primary outcome:** Repeatable evidence that the release-critical flows work.

Create test directories under `tests/Unit`, `tests/Feature`, and, if retained, `tests/Integration`. Remove `/tests/` from `.gitignore`. Use an isolated test database and factories/seeders that reflect the canonical schema.

| Test layer | Minimum coverage |
|---|---|
| Migration tests | Fresh migration, rollback, foreign keys, unique constraints, and seed execution. |
| Unit tests | Commission formulas, product overrides, points credit/debit, referral eligibility, state transitions, and idempotency decisions. |
| Feature tests | Login/logout, consumer access, admin authorization, product browsing, product buy flow, wallet withdrawal, gift redemption, and admin approvals. |
| API security tests | Missing/invalid signatures, replayed events, unauthorized points credit, rate limits, malformed requests, and cross-user access attempts. |
| API integration tests | Click → conversion → commission → cashback → referral flow, including retry behavior. |
| Contract tests | Request and response examples generated from the OpenAPI contract. |
| Browser/E2E tests | At minimum, login, consumer dashboard, product purchase redirect, and one admin workflow. |

Remove `|| true` from `.github/workflows/ci.yml`. The CI pipeline should fail if Composer installation, migrations, static checks, tests, contract validation, or security checks fail. Add PHP version coverage for the supported runtime and persist test/coverage artifacts.

Publish a versioned OpenAPI document for `/api/affiliate/*`, `/api/points/*`, `/api/referral/*`, health endpoints, and any admin API endpoints intended for external use. Document authentication, request schemas, response schemas, error formats, idempotency headers, and rate limits.

**Gate 5:** CI is red on failure, green only when the clean database, test suite, API contract checks, and security tests all pass. This is the minimum gate for lifting the release block.

## 9. Phase 6 — DevOps, Observability, Configuration, and Documentation

**Estimated effort:** 24–48 hours
**Depends on:** Phase 5
**Addresses:** `OPS-001`, `DOC-001`, low DevOps/observability scores
**Primary outcome:** Repeatable deployment and diagnosable production behavior.

Add a complete sanitized `.env.example` covering application identity/key, database, session, mail, cache, logging, storage, partner credentials, and security settings. Add a configuration validation command that fails clearly when required values are missing or unsafe defaults are present.

Document the actual deployment target. If Hostinger remains the target, define document root, PHP version/extensions, writable directories, cron/scheduler behavior, queue behavior, database migration procedure, storage linking, mail configuration, backup procedure, and rollback procedure. If containerized deployment is desired, add a Dockerfile and Compose configuration only after defining the supported production shape; do not add Docker merely to improve a score.

Add structured health checks for application, database, cache, queue, and partner dependency status. Add error-rate, latency, conversion-processing, points-credit, failed-login, queue-lag, and payout metrics. Centralize correlation IDs across click, conversion, commission, and points logs.

Replace or update README links to absent `/docs` files. Create a `docs/` set containing architecture, database schema, API contract, deployment, operations, security model, incident response, and ADRs. Include the generated audit report as a historical baseline, not as live product documentation.

**Gate 6:** A new engineer can configure the application from `.env.example`, deploy it using the documented procedure, verify health checks, locate correlated logs, restore a backup, and execute a documented rollback.

## 10. Phase 7 — Affiliate Integrations and Performance Validation

**Estimated effort:** 40–80 hours per first production integration; 16–32 hours per additional integration
**Depends on:** Phases 1–6
**Primary outcome:** Real affiliate-network behavior rather than generic URL scaffolding.

Implement a partner adapter boundary around program-specific URL generation, click parameters, conversion callbacks, commission rules, and webhook authentication. Start with one network and prove the complete lifecycle before adding others. The adapter should not allow partner-specific logic to leak into controllers.

Replace generic `Link::generateAffiliateUrl()` behavior with a tested adapter call where the program requires network-specific parameters. Define callback verification, event mapping, currency handling, refund/reversal behavior, and commission reconciliation.

Run profiling and load tests against product browsing, click tracking, conversion reporting, points credit, admin dashboards, and analytics queries. Use measured query plans to address N+1 queries, missing indexes, and long-running reports. Add queue workers for non-blocking GeoIP, notifications, reconciliation, and reporting tasks only where profiling demonstrates value.

**Gate 7:** One real or sandbox partner integration has verified click attribution, authenticated conversion callbacks, idempotent retries, reconciliation reporting, and measured latency/error targets. Performance is no longer marked NOT MEASURABLE.

## 11. Phase 8 — Controlled Production Rollout

**Estimated effort:** 12–24 hours excluding change-window coordination
**Depends on:** Gates 0–7
**Primary outcome:** A reversible, monitored release.

Deploy first to a staging environment using the same migration and configuration process intended for production. Run smoke tests, security tests, data reconciliation checks, and a limited synthetic click/conversion flow. Confirm that no real payout or points mutation can occur from test data unless explicitly isolated.

Use a canary or limited-partner rollout. Monitor authentication failures, API rejection rates, click-to-conversion attribution, duplicate event rejection, points ledger deltas, commission totals, queue lag, database errors, and latency. Establish a named incident owner and rollback decision threshold.

After the observation window, expand traffic gradually. Keep the pre-release backup, migration rollback strategy, and feature flags available until financial reconciliation confirms that the new pipeline matches expected totals.

**Gate 8:** Production release is approved only when staging and canary evidence is attached to the release record, rollback is tested, financial totals reconcile, and all P0/P1 audit findings are closed or formally accepted by an accountable business owner.

## 12. Dependency and Sequencing Matrix

| Phase | Must precede | Can proceed in parallel |
|---:|---|---|
| 0 | All implementation work | Documentation inventory and test-plan drafting. |
| 1 | 2–5 | Drafting OpenAPI and test cases against the approved contracts. |
| 2 | 3–5 | Non-runtime documentation and security threat modeling. |
| 3 | 4–5 | Admin UI cleanup that does not alter financial behavior. |
| 4 | 5–6 | Frontend accessibility review and documentation updates. |
| 5 | 6–8 | Additional unit tests and API contract authoring. |
| 6 | 7–8 | Integration adapter design and performance-test planning. |
| 7 | 8 | Operational runbook rehearsal. |
| 8 | Final release | Post-release hardening and additional integrations. |

## 13. Release Gates Checklist

The release block should be lifted only when every P0 control below is demonstrably passing.

| Gate | Required evidence |
|---|---|
| Database | Clean migration from empty database, rollback test, foreign-key verification, and schema/runtime contract tests. |
| Authentication | Successful consumer and admin login using the same guard; unauthorized route tests; session regeneration test. |
| API security | Points credit and conversion endpoints reject unauthenticated, unauthorized, malformed, expired, and replayed requests. |
| Financial integrity | Exactly-once conversion, commission, cashback, referral, withdrawal, and redemption tests. |
| CI | No `|| true` around test execution; required checks fail the pull request when broken. |
| Testing | Release-critical unit, feature, API security, migration, and at least one E2E flow pass. |
| Credentials | No fixed production password or secret in source; deployed credentials rotated. |
| Operations | `.env.example`, configuration validation, health checks, logs, backup, restore, and rollback are documented and verified. |
| Documentation | README links resolve and API/database/deployment behavior matches the code. |

## 14. Recommended First Sprint

The first sprint should not add new affiliate networks or AI features. It should complete Phase 0, begin Phase 1, and close the most dangerous security exposure in Phase 2. The concrete first-sprint sequence is: protect the branch and back up data; write the canonical conversion/commission contracts; reorder migrations; remove the fixed admin password; implement guard-based login; protect points credit; and add the first migration/auth/API tests. The sprint ends only after a clean schema and authenticated points API test pass in CI.

## 15. References

[1]: audit/reports/latest.md — dynamic audit findings, measured discovery counts, release decision, and release score.
[2]: audit/release-score.json — calculated category scores and explicit scoring controls.
[3]: audit/issues.json — issue IDs, severity, evidence, impact, estimated effort, and priority.
[4]: audit/database.json — migration dependencies, schema references, indexes, and transaction inventory.
[5]: audit/api.json — discovered endpoints, middleware context, validation, documentation, and test status.
[6]: audit/security.md, audit/testing.md, audit/release-readiness.md — security, testing, and release gate details.
[7]: audit/architecture.json, audit/dependencies.json — detected architecture layers and dependency/runtime flows.
