# ZenithSoles Affiliates — Detailed Phase-Wise Implementation Plan

**Purpose:** Convert the Business Viability Assessment into an execution plan that an AI agent can follow without inventing business facts, credentials, partner behavior, financial assumptions, legal decisions, or production evidence.

**Plan status:** Repository code hardening is substantially complete and has been pushed to `main` in commit `129aaac`. This plan therefore separates **already implemented controls**, **next implementation work**, and **external staging/business decisions that an AI agent must not fabricate**.

## 1. Executive implementation strategy

The recommended strategy is **GO for a narrow, evidence-driven 90-day pilot; NO for broad public scale until economics, partner supply, repeat behavior, payout reliability, and fraud controls are proven**. The product should not compete as a generic “another cashback website.” It should select one niche, secure a small set of verified partner programs, acquire a reachable community, and measure contribution margin before increasing cashback or paid acquisition. [1]

The plan has two parallel tracks:

| Track | Objective | Status |
|---|---|---|
| Product and engineering | Make the platform safe, testable, observable, and usable for a controlled beta | Core security, attribution, financial atomicity, CI, documentation, and staging tooling implemented |
| Business and staging validation | Prove partner supply, unit economics, retention, fraud rate, payout reliability, and operational readiness | Pending; requires named owners, approved staging credentials, partner access, and real business decisions |

> **Non-negotiable rule:** A passing local test proves only the behavior exercised in that local environment. It does not prove a partner contract, production topology, payout provider, legal approval, capacity limit, or business model.

## 2. Anti-hallucination execution contract

Every AI agent working on this repository must follow the contract below. If a required input is absent, the agent must stop and ask for the missing input or mark the task **Blocked**. It must never fill the gap with a plausible value.

### 2.1 Evidence hierarchy

| Priority | Evidence type | Permitted use |
|---:|---|---|
| 1 | Current repository source, migration, test, CI file, generated artifact, or command output | Treat as implementation evidence for exactly what it proves |
| 2 | User-provided or owner-approved partner/business document | Treat as an approved business requirement only for the stated scope |
| 3 | Official provider/framework documentation | Use for external technical prerequisites and command semantics |
| 4 | Public competitor or market information | Use only as directional context; label vendor claims and estimates |
| 5 | AI inference, memory, or generic best practice | Never present as project fact; may be proposed only as an unapproved option |

### 2.2 Unknowns must remain unknown

The agent must not invent affiliate commission rates, cashback percentages, reversal windows, partner endpoints, HMAC secrets, API keys, database credentials, payout-provider behavior, user counts, conversion rates, CAC, LTV, legal classification, GeoIP vendor, traffic capacity, or approval status. Every such value must be recorded as one of **Confirmed**, **Unknown**, **Proposed for approval**, **Not applicable**, or **Blocked**.

### 2.3 Safe execution rules

| Rule | Required behavior |
|---|---|
| Read-only default | Audits, route inspection, health checks, exports, and test runs are the default mode |
| Financial mutation guard | Never call valid conversion, points-credit, withdrawal, payout, refund, or provider mutation against production; use only approved staging and an explicit mutation flag |
| Destructive command guard | Never run `migrate:fresh`, database deletion, bulk deletion, cache deletion, force push, or production reset unless the release owner has explicitly approved the exact target and consequence |
| Credential guard | Never request, print, commit, log, or attach secrets; use environment injection and redact output |
| Scope lock | Modify only the files listed in the current phase; unrelated changes are reverted or separately proposed |
| Failure handling | Stop at the first failed gate; do not suppress errors with `|| true`, ignore exit codes, or claim success from partial output |
| Evidence integrity | Record command, UTC timestamp, commit SHA, environment class, exit code, and artifact path for every gate |
| Source-of-truth rule | If two artifacts conflict, stop and report the conflict; do not choose the more convenient value |
| Approval rule | An AI agent may prepare evidence and recommendations, but only the named owner may close a staging blocker or approve production |

### 2.4 Required task envelope

Before executing any task, the agent must create a short task envelope containing the following fields:

```text
Task ID:
Phase:
Objective:
Allowed files/commands:
Environment: local | staging | production
Mutation mode: read-only | approved-staging-mutation
Known facts:
Unknowns:
Required user/owner inputs:
Expected evidence:
Stop conditions:
```

If `Unknowns` contains a value required to execute the task, the task status is **Blocked** until an owner supplies it. The agent must not proceed by substituting a default.

## 3. Current baseline and what is already complete

The following repository-side controls are already implemented and must not be re-described as pending code work unless a new regression is found:

| Control | Current evidence |
|---|---|
| Laravel 12 upgrade | `composer.json`/`composer.lock`; Laravel 12 regression evidence |
| Dependency security | Composer audit reports 0 advisories |
| Authentication | Laravel guard login/logout, session regeneration/invalidation, admin authorization |
| Partner mutation security | HMAC-SHA256 middleware, key/timestamp checks, protected mutation routes |
| Attribution | Shared affiliate tracking service used by API and consumer purchase flow |
| Financial integrity | Atomic/idempotent points, withdrawal, commission, redemption, refund, and payout transitions |
| Payout reconciliation | Deterministic CSV checker with duplicate/mismatch detection |
| API throttling | Named route-specific throttles for partner mutation paths |
| Security headers | Global middleware with environment-aware HSTS |
| Observability baseline | Correlation fields in click/conversion/reward/ledger logs |
| CI and tests | PHP matrix, Composer audit, lint, migrations, PHPUnit, coverage artifact, route and contract gates |
| Release documentation | OpenAPI, architecture, ADR, operations runbook, staging guide, acceptance record, blocker register |

The latest local evidence includes **11 passing PHPUnit tests and 46 assertions**, clean migrations, PHP lint, Composer audit with zero advisories, contract validation, dynamic audit generation, and `git diff --check`. This evidence is strong for local implementation readiness but does not close external staging rows. [2]

## 4. Phase 0 — Baseline lock and owner setup

**Objective:** Freeze the implementation baseline and establish ownership before new feature or growth work begins.

| Item | Details |
|---|---|
| Owner | Release owner plus product owner |
| Inputs | Commit `129aaac`, current `STAGING_BLOCKER_REGISTER.md`, current partner/business requirements |
| Dependencies | None |
| Agent scope | Read repository and artifacts; do not modify business logic |
| Outputs | Approved baseline SHA, named owners, due dates, pilot scope, blocker register copy |
| Acceptance criteria | No unresolved ambiguity about target niche, initial partners, environment, or owner for each P0/P1 blocker |
| Evidence | `git rev-parse HEAD`, `git status --short`, signed/recorded owner assignment, blocker register diff |
| Stop conditions | Missing release owner, missing pilot owner, conflicting branch state, or unapproved target environment |

The release owner must also confirm whether this pilot is consumer-facing, B2B infrastructure, or a hybrid. The agent must not assume that a consumer cashback product and a merchant SaaS product have the same success criteria.

## 5. Phase 1 — Choose the commercial wedge and target customer

**Objective:** Replace generic cashback positioning with one measurable niche and one primary customer segment.

| Work item | Required decision | Evidence required |
|---|---|---|
| Niche selection | Choose exactly one initial category or community | Owner-approved one-page positioning brief |
| Target persona | Define who will click, buy, refer, and withdraw | Interview notes or approved persona document |
| Value proposition | State why the user chooses ZenithSoles over incumbents | Approved message tested with target users |
| Distribution hypothesis | Select creator, WhatsApp, Telegram, SEO, community, or other channel | Channel owner and test plan |
| Partner hypothesis | Define which merchants/programs are needed | Partner shortlist with source and approval status |
| Success metric | Define first-pilot go/no-go metrics before launch | Signed metric sheet; no post-hoc thresholds |

**AI restrictions:** The agent may propose candidate wedges, but cannot select one as final without product-owner approval. It must not claim a niche is underserved based only on generic intuition or a single vendor article.

**Exit criteria:** One target segment, one primary acquisition channel, three-to-five partner targets, and a written reason why the proposition is differentiated. If these are not approved, stop before building broad catalogue features.

## 6. Phase 2 — Secure partner supply and formalize contracts

**Objective:** Obtain the minimum partner inventory and technical contracts needed for a controlled beta.

| Work item | Owner | Required evidence |
|---|---|---|
| Partner onboarding | Partner integration owner | Written approval or sandbox access from each partner |
| Field mapping | Integration owner | Request/response examples with secrets removed |
| Signature contract | Security/integration owner | Raw-body signing, key ID, timestamp, tolerance, and rotation policy |
| Retry contract | Integration owner | Retry count, backoff, timeout, duplicate delivery, and dead-letter behavior |
| Attribution contract | Product/integration owner | Click ID, partner event ID, order ID, attribution window, cancellation rules |
| Commission contract | Finance/partner owner | Commission basis, exclusions, currency, approval and reversal terms |
| Data contract | Privacy/security owner | Personal data fields, retention, deletion, and access rules |

**Acceptance criteria:** At least three active partner programs are approved for staging or pilot, each has a written contract, and the partner checker passes negative and valid staging certification. No partner may be marked active because an API endpoint merely exists in documentation.

**Stop conditions:** Missing partner authorization, unknown callback format, unverified signature semantics, unexplained field mapping, or any request to use production credentials in local tests.

## 7. Phase 3 — Build and validate unit economics

**Objective:** Prove whether each confirmed order can generate positive contribution margin after rewards and operational costs.

### 7.1 Required data fields

| Field | Source | Status rule |
|---|---|---|
| Gross order value | Partner export or approved fixture | Unknown until source is supplied |
| Commission received | Partner terms/export | Do not infer from cashback rate |
| Cashback reward | Approved reward policy and ledger | Must distinguish pending from confirmed |
| Referral reward | Approved referral policy | Include only if actually paid |
| Payout/payment cost | Provider terms or invoice | No zero assumed without evidence |
| Reversal/refund loss | Partner history or pilot data | Must be measured, not ignored |
| Fraud/dispute loss | Pilot/support data | Track as a separate cost |
| CAC | Channel experiment data | Do not call organic traffic “free” without labor allocation decision |
| Support/infrastructure cost | Approved finance model | Use actual or explicitly labeled estimate |

### 7.2 Calculation contract

```text
Net contribution per confirmed order
= commission received
− cashback/reward
− referral share
− payment/payout cost
− reversal/refund/fraud loss
− variable support and infrastructure cost
```

The agent may calculate from owner-supplied data, but must not manufacture a forecast. Every calculated result must include the input file, time period, currency, inclusion/exclusion rules, and whether values are actual, partner-provided, or estimated.

**Exit criteria:** Category-level and partner-level contribution margin is positive under base case, reversal case, and conservative case, or the owner explicitly approves a capped subsidy experiment with a maximum loss budget.

**Stop conditions:** Missing commission terms, mixed currencies, unclear gross/net basis, missing reversal data, or unexplained negative margin.

## 8. Phase 4 — Product and frontend pilot readiness

**Objective:** Ensure a first-time user can discover, click, understand status, receive rewards, and obtain support.

| Capability | Implementation task | Acceptance evidence |
|---|---|---|
| Discovery | Add focused deal/category landing experience | Approved user journey and working staging URL |
| Trust | Explain tracking, confirmation delay, reversals, and payout timing | User-facing copy reviewed by product/support owner |
| Status | Show pending/confirmed/reversed/paid states consistently | UI test plus matching ledger fixture |
| Support | Add missing-cashback and dispute intake path | Test ticket or approved support workflow |
| Mobile UX | Validate core flow on supported viewport sizes | Browser screenshots/test results |
| Accessibility | Add automated checks for core pages | CI artifact and threshold decision |
| SEO | Add metadata, canonical, sitemap/robots decisions where applicable | Automated check and owner approval |
| Assets | Add explicit build/version/cache-busting pipeline | Reproducible build output and deployment note |

**AI restrictions:** The agent must not invent SEO claims, accessibility compliance, user reviews, cashback rates, or product screenshots. Content requiring legal or commercial approval remains Draft until approved.

**Exit criteria:** A test user can complete the full read-only discovery and tracked-click path without broken links, misleading reward promises, or unhandled error states.

## 9. Phase 5 — Partner integration and attribution certification

**Objective:** Prove that real staging partner traffic creates exactly one correct attribution and reward chain.

### 9.1 Certification sequence

| Step | Mode | Required proof |
|---:|---|---|
| 1 | Read-only | Health, route, TLS, and environment checks |
| 2 | Negative | Invalid HMAC, expired timestamp, malformed signed payload, wrong key |
| 3 | Controlled mutation | One disposable conversion using approved staging fixture |
| 4 | Replay | Same partner event and idempotency key produce no duplicate mutation |
| 5 | Conflict | Same event with conflicting payload is rejected or quarantined per contract |
| 6 | Points credit | One approved credit, balance delta, ledger row, and replay result |
| 7 | Rate limit | Expected `429`, bucket isolation, retry behavior, alternate-header test |
| 8 | Evidence | Correlation IDs, conversion/commission/reward IDs, sanitized logs |

**Mutation authorization:** Valid mutation tests require staging-only URL, staging-only credentials, disposable IDs, an approved test window, and explicit `--allow-mutations`. The agent must refuse to run them when any input is missing.

**Exit criteria:** Partner owner and security owner sign the staging acceptance record. A local negative test alone cannot close this phase.

## 10. Phase 6 — Financial, fraud, and reconciliation controls

**Objective:** Prove the financial lifecycle is correct under normal, duplicate, rejected, timed-out, and reversed scenarios.

| Scenario | Required evidence | Stop condition |
|---|---|---|
| Commission approval | State transition, actor, timestamp, reference | Approval bypass or missing actor |
| Commission payment | Approved-before-paid proof, payout reference | Paid without approval |
| Withdrawal | One debit and one redemption | Double debit or missing redemption |
| Duplicate withdrawal | Same idempotency key replay | Balance changes twice |
| Redemption rejection | Exactly-once refund | Double refund or no refund |
| Provider timeout | Retry and final state | Unknown financial state |
| Duplicate callback | Idempotent callback handling | Duplicate refund/credit |
| Reconciliation | Platform/provider exports and machine-readable report | Any unresolved amount/status/currency/reference mismatch |
| Ledger audit | Correlation IDs and references | Financial entry cannot be traced |

**Exit criteria:** Payout owner closes the reconciliation and refund rows with sanitized evidence. No real funds should move during certification unless a separate written approval explicitly authorizes it; a no-funds sandbox is preferred.

## 11. Phase 7 — Deployment, database, secret, and rollback readiness

**Objective:** Prove the application can be deployed and recovered safely in a production-like staging environment.

| Work item | Required evidence | Owner |
|---|---|---|
| Host and PHP prerequisites | PHP 8.2+, required extensions, web server, Composer, MySQL | Deployment owner |
| Document root | Domain points only to Laravel `public/` | Deployment owner |
| Environment injection | Secret version references, redaction review, no source/CI leakage | Security owner |
| Secret rotation | New secret works; old secret fails; no stale cache | Security owner |
| Migration rehearsal | Backup ID, schema diff, duration, lock observations, data counts | Database owner |
| Restore rehearsal | Checksum, isolated restore, row/count verification | Database owner |
| Application rollback | Previous artifact, rollback log, health and smoke results | Release owner |
| Cache/permissions | Writable `storage` and `bootstrap/cache`; optimization succeeds | Deployment owner |
| TLS and headers | HTTPS, security headers, HSTS behavior | Security owner |
| Queue/cron | Required scheduled/worker behavior or explicit out-of-scope decision | Operations owner |

**Stop conditions:** Production root path exposed, `APP_DEBUG=true`, secrets in logs, migration without verified backup, rollback not tested, or use of SQLite as a production substitute for the approved MySQL topology.

## 12. Phase 8 — Observability, incident response, and capacity

**Objective:** Establish operational evidence rather than only application logging.

| Area | Implementation/evidence task | Exit criteria |
|---|---|---|
| Centralized logs | Ship structured logs with click/conversion/user correlation fields | Logs searchable in approved platform and redaction reviewed |
| Alerts | Define error, payout, reconciliation, auth, and latency alerts | Test alert received and acknowledged |
| On-call | Publish escalation roster and rollback authority | Drill completed with timestamps |
| Incident response | Exercise duplicate conversion, payout timeout, and secret compromise scenarios | Runbook followed and lessons recorded |
| Capacity | Run bounded load test with approved traffic model | p95/p99, errors, CPU/memory/DB/queue graphs and stop condition |
| Runtime profiling | Profile slow routes/queries under representative data | Findings linked to optimization tickets |

The existing smoke harness is deliberately bounded and read-only by default. It is not capacity certification. The AI agent must never convert smoke latency into a claim about maximum users, requests per second, or production capacity.

## 13. Phase 9 — Pilot launch and business validation

**Objective:** Run a controlled 90-day pilot before scale investment.

| Period | Scope | Evidence |
|---|---|---|
| Days 1–15 | Finalize niche, partners, reward/fraud policy, support process | Signed pilot brief and partner terms |
| Days 16–30 | 50–100 invited users; no broad paid scale | Certified staging and beta cohort log |
| Days 31–60 | 3–5 organic creator/community channels | Channel-level click, order, confirmation, repeat data |
| Days 61–90 | Capped acquisition experiments | Contribution margin and retention report |

### Required weekly metrics

| Metric | Decision purpose |
|---|---|
| Active shoppers | Measures real demand rather than registrations |
| Click-to-order conversion | Measures traffic quality and attribution |
| Confirmed/reported ratio | Measures partner validation and reversals |
| Commission per confirmed order | Establishes revenue ceiling |
| Reward cost per order | Establishes liability |
| Contribution margin | Determines whether scaling is economically rational |
| 30-day repeat rate | Measures retention and LTV potential |
| Fraud/dispute rate | Measures operational burden and leakage |
| Settlement time | Measures user trust and working-capital pressure |
| Missing-reward or attribution ticket rate | Measures tracking/support quality |

**Go/no-go criteria:** At least three verified partner programs, positive contribution margin after all variable costs, repeat users, manageable dispute rate, no unexplained financial discrepancies, and at least one repeatable acquisition channel not dependent entirely on paid advertising.

## 14. Phase 10 — Enterprise maturity backlog after pilot proof

This phase must not be started merely to increase the audit score. It should be prioritized only when product evidence shows a need.

| Gap | Trigger to implement | Candidate work |
|---|---|---|
| Queues/workers | Synchronous processing causes latency or retry pressure | Queue driver, worker deployment, retries, dead-letter handling |
| Merchant adapters | More than one partner requires repeated custom mapping | Adapter interface, contract tests, partner-specific modules |
| Deployment automation | Manual deploy/rollback creates risk | Build artifact, Docker/VPS deployment, rollback automation |
| Repository abstraction | Persistence logic becomes difficult to test/change | Focused repository/query boundaries for high-risk domains |
| Maintainability | Complexity or defect rate rises | Static analysis, complexity thresholds, refactoring tickets |
| Frontend quality | Public acquisition begins | Accessibility, SEO, asset pipeline, browser regression suite |
| Performance | Pilot traffic approaches measured limits | Profiling, indexes, eager loading, cache/queue tuning |

The AI agent must not add abstractions, queues, Docker, or adapters only because a score control is false. Each addition requires a product or operational trigger, an owner, a bounded scope, tests, and evidence of benefit.

## 15. Phase gate format for every implementation phase

Every phase must end with a gate record using this structure:

```text
Phase ID:
Release commit:
Environment:
Owner:
Scope completed:
Scope intentionally not completed:
Files changed:
Commands executed:
Exit codes:
Tests passed:
Evidence artifact paths:
Known limitations:
Open blockers:
Approval status: Draft | Ready for review | Approved | Blocked
```

### Definition of Done

A phase is **Done** only when the code or decision is implemented within scope, tests pass, evidence is archived, documentation is updated, known limitations are recorded, and the responsible owner accepts the outcome. “The agent ran without an error” is not a Definition of Done.

## 16. Final production go/no-go gate

Production approval is **No-Go** if any P0/P1 row in `STAGING_BLOCKER_REGISTER.md` is open, unassigned, unevidenced, or has an uncleared stop condition. It is also No-Go if the unit economics are unknown, partner supply is unapproved, rollback is untested, secret rotation is unverified, payout reconciliation has exceptions, or legal/privacy decisions are missing.

Production may be requested only when the following evidence package is complete:

| Gate | Required status |
|---|---|
| Partner supply | Three-to-five partner programs approved and certified |
| Attribution | Valid, replay, conflict, and retry scenarios pass |
| Financial | Wallet, commission, redemption, refund, and reconciliation evidence complete |
| Economics | Positive contribution margin or approved capped experiment |
| Security | Secrets, HMAC, rate limits, headers, redaction, and incident controls verified |
| Database | Production-like migration, backup, restore, and rollback rehearsed |
| Operations | Central logs, alerts, on-call, and incident drill complete |
| Capacity | Staging load evidence and stop conditions recorded |
| Product | Core user journey, support, accessibility, SEO, and assets accepted for launch scope |
| Governance | License and GeoIP/privacy decisions recorded |
| Ownership | All blocker rows closed with named approvals |

## 17. AI-agent execution checklist

Before each action, the agent must answer these questions in its internal task record:

1. What exact repository or owner evidence supports this action?
2. Is the target environment local, staging, or production?
3. Does the action mutate financial state, credentials, customer data, or deployment state?
4. Are all required inputs explicitly supplied and approved?
5. What exact files and commands are in scope?
6. What result would cause an immediate stop?
7. What artifact will prove the result?
8. Who is authorized to approve closure?

If any answer is unknown, the agent must report **Blocked — missing evidence or authorization** instead of guessing.

## References

1. [`CONCEPT_VIABILITY_ASSESSMENT.md`](CONCEPT_VIABILITY_ASSESSMENT.md), commercial strengths, gaps, differentiation, unit economics, and 90-day pilot plan.
2. [`STAGING_READINESS_REPORT.md`](STAGING_READINESS_REPORT.md), verified local controls and staging boundary.
3. [`END_TO_END_GAP_ASSESSMENT.md`](END_TO_END_GAP_ASSESSMENT.md), severity-wise technical, operational, financial, product, and governance gaps.
4. [`STAGING_BLOCKER_REGISTER.md`](STAGING_BLOCKER_REGISTER.md), owner, due-date, evidence, stop-condition, and approval register.
5. [`FINAL_STAGING_HANDOFF_CHECKLIST.md`](FINAL_STAGING_HANDOFF_CHECKLIST.md), release gates and sign-off requirements.
6. [`docs/STAGING_OWNER_EXECUTION_GUIDE.md`](docs/STAGING_OWNER_EXECUTION_GUIDE.md), staging execution procedure.
7. [`docs/CONTROL_EXECUTION_MATRIX.md`](docs/CONTROL_EXECUTION_MATRIX.md), credential-free versus staging-only control boundary.
8. [`docs/RELEASE_OPERATIONS_RUNBOOK.md`](docs/RELEASE_OPERATIONS_RUNBOOK.md), deployment, secret, migration, payout, rollback, and incident procedures.
9. [`PARTNER_INTEGRATION_CONTRACT.md`](PARTNER_INTEGRATION_CONTRACT.md), partner authentication, idempotency, retry, attribution, and payout contract.
