# ZenithSoles Affiliates — Current Project Status

**Status:** Active source of truth  
**Last repository verification:** 25 August 2026  
**Verified source:** `main` / `origin/main` synchronized; verify the exact tip with `git rev-parse --short HEAD`
**Branch:** `main`  
**Remote:** `origin/main` synchronized  
**Environment verified:** Local credential-free verification only  
**Production status:** **Not approved**

> This document is the current implementation and decision-status reference. It must be read before starting new work. Missing values are unknown; an AI agent or developer must not invent them. Archived documents are historical only and must not be used as current requirements, approvals, partner evidence, financial policy, or production-readiness proof.

## 1. Executive status

ZenithSoles Affiliates is currently a Laravel 12 affiliate-comparison platform foundation. The owner-provided product direction is an external-merchant comparison service: shoppers search or compare products in one place, see permitted offer and price-history information, and follow tracked referral links to merchant platforms. ZenithSoles does not currently act as the merchant, does not perform checkout or fulfilment, and has no verified production partner access in this repository.

The technical foundation has passed local verification. The commercial pilot remains **Phase 1 blocked** because partner access, data permissions, bounded pilot scope, detailed reward and reversal policy, numeric metrics, named owners, dates, and formal sign-offs are incomplete. The platform must not be represented as production-ready or as having live affiliate inventory until approved staging evidence exists.

## 2. Owner-provided business direction recorded

The following direction has been supplied by the owner and is recorded as a **proposed commercial decision awaiting formal template completion and sign-off**:

| Area | Recorded direction | Approval status |
|---|---|---|
| Customer experience | Compare products and offers from multiple online platforms in one place; rank or display useful options from top to bottom | Direction recorded; ranking policy pending |
| Merchant relationship | User is redirected to the external merchant and completes the purchase there | Direction recorded; partner terms pending |
| Long-term audience | All online shoppers | Broad direction recorded; measurable pilot segment pending |
| Product scope | Top 100 products on each platform and Google based on research | Direction recorded; source, definition, category scope, and permissions pending |
| Reward model | Post-confirmation affiliate reward points, not immediate cashback | Model selected in template; formal approvals pending |
| Commission allocation | Of received affiliate commission: 40% customer points, 40% owner/business share, 20% platform scaling and maintenance reserve | Owner direction recorded; Finance/Payout approval and settlement basis pending |
| Points conversion | 10 points = ₹1 for voucher redemption | Owner direction recorded; voucher provider, liability, expiry, limits, and approval pending |
| Reward timing | Points should become usable only after partner confirmation; owner estimates confirmation/payment may take approximately 2–3 months | Partner-specific terms and reversal policy pending |
| Distribution | WhatsApp, Telegram, creators, SEO, communities, Facebook, and Instagram | Direction recorded; channel targets and budget pending |
| Future scope | ZenithSoles may later list its own products | Future scope only; outside current affiliate-comparison pilot |

The 40/40/20 split is interpreted as a split of **received affiliate commission**, not a percentage of the customer order value. For example, ₹100 of received commission would directionally allocate ₹40 to customer points, ₹40 to owner/business share, and ₹20 to platform reserve. This is not yet a live financial rule because partner deductions, net-versus-gross settlement, returns, reversals, voucher cost, and accounting approval remain unresolved.

## 3. Completed implementation

### 3.1 Application and security hardening

The repository has been upgraded to Laravel 12 with PHP 8.2 compatibility and PHPUnit 11. Composer dependency auditing currently reports zero advisories. Database migrations and compatibility changes were aligned with the application models. Authentication uses Laravel guards, hardcoded administrator credentials were removed, and sensitive ownership checks were added.

Partner mutation security uses HMAC-SHA256 over `timestamp + '.' + raw request body` with `X-Affiliate-Key`, `X-Affiliate-Timestamp`, and `X-Affiliate-Signature`. Idempotency keys and deterministic ledger keys protect conversion, points, payout, redemption, and refund paths. Atomic transactions and row locks are used for financial state transitions. Endpoint-specific throttles, correlation identifiers, structured logs, security headers, and production-only HSTS behavior are implemented.

### 3.2 API-independent catalog and comparison preview foundation

The repository now contains an additive `product_price_snapshots` schema, an Eloquent snapshot model, a transactional snapshot service, a deterministic product-ranking service, a snapshot-backed public comparison path, a source-adapter interface, guarded comparison feature switches, and a local/testing-only synthetic preview seeder. Snapshot records preserve source tags, nullable unknown values, price observations, availability, rating, discount, and timestamps. The foundation does not pretend that a missing value is available and does not contain hidden referral-margin defaults.

The ranking service accepts caller-supplied normalized features and approved weights. It is not an unrestricted autonomous agent. The current preview orders offers by lowest known observed price with stable ties; this is a transparent preview rule, not the final commercial ranking policy. Before production use, ranking weights, user disclosure, source permissions, product matching, freshness rules, and commercial influence must be approved and tested with real partner data. `COMPARISON_REWARDS_ENABLED`, `COMPARISON_VOUCHERS_ENABLED`, and `COMPARISON_GIFTS_ENABLED` remain disabled by default.

### 3.3 Evidence-gated execution controls

The pilot decision template, remaining-decisions checklist, owner/timeline proposal, owner-action package, report-only validator, strict validator, validator regression tests, Phase 1 owner-input capture, Phase 1 gate evidence, partner research, Phase 3 foundation evidence, and documentation-cleanup evidence are present.
 CI runs the pilot validator in report-only mode, and an explicitly enabled release workflow can enforce the strict gate through `ENFORCE_PILOT_DECISION=true`.

## 4. Verified local evidence

The following checks were run against the current repository state. They prove local code and documentation integrity only; they do not prove partner approval, production readiness, revenue, legal compliance, or live payout behavior.

| Verification | Result |
|---|---|
| Laravel framework constraint | Laravel `^12.0` |
| PHP constraint | `^8.2` |
| PHPUnit constraint | `^11.0` |
| PHPUnit suite | **19 tests, 91 assertions passed** |
| Python guardrail tests | **4 tests passed** |
| Python compilation | Passed |
| Clean SQLite migration | Passed |
| PHP lint | Passed |
| Composer strict validation | Passed |
| Composer security audit | Passed; **0 advisories** |
| Release-contract validator | Passed; **34 required files** |
| Pilot report-only validator | Passed; correctly reports blocked |
| Pilot strict validator | Expected non-zero block |
| Audit JSON validation | Passed for current audit JSON set |
| Active stale-claim scan | No targeted stale claims found |
| `git diff --check` | Passed |
| Working tree | Clean |

## 5. Current Phase 1 gate

Phase 1 is **blocked_owner_input_required**. The current validator result is:

| Gate item | Current result |
|---|---:|
| Selected pilot models | 1 — Consumer affiliate comparison with post-confirmation reward points |
| Required fields still containing `[REQUIRED]` | **32** |
| Owner sign-off rows remaining | **4** |
| Production approval | `not_approved` |
| Strict release gate | Blocked until all required inputs and approvals are complete |

The exact machine-readable record is [`audit/phase1-gate.json`](audit/phase1-gate.json). The owner-input form is [`docs/PILOT_DECISION_INPUT_TEMPLATE.md`](docs/PILOT_DECISION_INPUT_TEMPLATE.md), and the step-by-step owner checklist is [`docs/OWNER_ACTION_PACKAGE.md`](docs/OWNER_ACTION_PACKAGE.md).

## 6. Pending work

### 6.1 Required commercial and owner decisions

The following must be supplied or approved before Phase 1 can close:

1. Define the first pilot category or bounded category set. “Top 100 across every platform” is a long-term direction, not yet a testable pilot boundary.
2. Define the measurable first-pilot user segment even if the long-term audience is all online users.
3. Confirm geography, language, pilot name, start date, end date, and staging URL.
4. Name Product, Release, Affiliate Integration, Engineering, Data/Privacy, Security, Finance/Payout, and Operations owners.
5. Approve the exact reward policy: received commission basis, net/gross deductions, 40/40/20 allocation, points ledger treatment, voucher liability, minimum redemption, expiry, limits, and failed redemption handling.
6. Approve partner-specific pending, confirmed, reversed, cancelled, returned, and usable states. Do not use a universal two-month promise unless partner terms support it.
7. Approve referral eligibility, referral reward, self-referral prevention, fraud review, reserve handling, and return/chargeback treatment.
8. Approve the gift program: eligibility, selection method, budget, cap, frequency, disclosure, and fraud/return handling.
9. Approve numeric pilot metrics and thresholds for conversion, confirmed commission, reward cost, contribution margin, repeat use, missing-attribution tickets, fraud/disputes, and settlement time.
10. Provide a written approval record and complete all four mandatory sign-offs: Product, Release, Security, and Finance/Payout.

### 6.2 Partner and data access

Amazon and Flipkart are currently documented as direct API candidates, not active production partners. Amazon access requires the current Creators API pathway and eligibility/onboarding. Flipkart has official affiliate API documentation, but registration, credentials, terms, and staging certification are still required. Meesho, Myntra, AJIO, Nykaa, Tata CLiQ, Snapdeal, and JioMart are candidates or research subjects only; a public link or creator page is not proof of catalog API, feed, attribution, or price-history rights.

Pending partner work includes written program approval, API or feed access, allowed fields, rate limits, deep-link rules, attribution windows, reporting, commission confirmation, reversals, data retention, and staging test credentials. An intermediary network may be used only after its specific campaigns, API/feed/deeplink access, terms, and permission for the intended comparison use are verified.

### 6.3 Technical work after approved access

After partner access is approved, the next implementation will add source adapters rather than rewrite the catalog core. It will cover partner authentication, product and offer mapping, product identity matching, price and availability refresh, rating normalization, deep links, click attribution, conversion ingestion, delayed points, reversal handling, voucher redemption, reconciliation, and user-facing disclosures. All live mutations must occur only in approved staging with disposable fixtures and explicit authorization.

### 6.4 Staging and production readiness

The following remain external staging blockers: real-but-disposable partner credentials, valid signed mutation tests, conversion and points certification, payout-provider behavior, MySQL migration, backup and restore, rollback, secret rotation, centralized logs and alerts, capacity evidence, privacy and data-retention review, licensing/terms review, and final owner sign-off. Production must remain blocked until the blocker register and final handoff checklist contain evidence for every required control.

## 7. Explicit non-claims

This repository does **not** currently claim any of the following:

- live Amazon, Flipkart, or other merchant credentials;
- approved affiliate contracts or guaranteed commission rates;
- live product catalogue, current prices, stock, ratings, or Google search data;
- permission to scrape, store, redistribute, or compare any platform’s data;
- a universal two-month confirmation period;
- guaranteed user rewards, voucher availability, or gift inventory;
- positive revenue, contribution margin, user metrics, or business viability;
- production deployment, Hostinger certification, or real-funds payout testing;
- legal, tax, privacy, licensing, or partner-term approval.

## 8. Document authority and archive policy

Use the following order when deciding what is current:

| Priority | Source | Use |
|---:|---|---|
| 1 | `CURRENT_PROJECT_STATUS.md` | Current implementation, decisions, blockers, and document authority |
| 2 | `README.md` | Repository overview and current operating boundary |
| 3 | `DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md` | Phase gates and anti-hallucination execution rules |
| 4 | `docs/PILOT_DECISION_INPUT_TEMPLATE.md` | Owner inputs and formal approvals |
| 5 | `docs/OWNER_ACTION_PACKAGE.md` and `audit/owner-action-package.json` | Owner action sequence, evidence requirements, stop conditions, and completion checks |
| 6 | `STAGING_BLOCKER_REGISTER.md`, `STAGING_READINESS_REPORT.md`, `FINAL_STAGING_HANDOFF_CHECKLIST.md` | Staging and release evidence |
| 7 | Specific contracts, runbooks, architecture, ADRs, and foundation evidence | Technical implementation details |
| 8 | `audit/*.json` and `audit/*.md` | Measured or historical evidence; use only with date, scope, and limitations |
| 9 | `docs/archive/**` | Historical reference only; never current instructions or approval evidence |

The archive index is [`docs/archive/README.md`](docs/archive/README.md). It contains superseded status reports, old plans, stale quick-start material, obsolete Laravel 10 guidance, and duplicate generic rule files. Dated audit snapshots may remain active as historical evidence, but they must not override this document or current phase-gate results.

## 9. Safe next action

The next safe action is to follow [`docs/OWNER_ACTION_PACKAGE.md`](docs/OWNER_ACTION_PACKAGE.md) and complete and approve [`docs/PILOT_DECISION_INPUT_TEMPLATE.md`](docs/PILOT_DECISION_INPUT_TEMPLATE.md).
 Until then, development may continue only on API-independent fixtures, source adapters, documentation, UI scaffolding, validators, and tests that do not claim real partner behavior. No agent may select a category, partner, commission rule, reversal rule, metric threshold, owner, date, or production setting on the owner’s behalf.

## References

1. [`README.md`](README.md) — Current repository overview and operating boundary.
2. [`DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md`](DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md) — Evidence-gated implementation plan.
3. [`docs/PILOT_DECISION_INPUT_TEMPLATE.md`](docs/PILOT_DECISION_INPUT_TEMPLATE.md) — Owner decision and approval template.
4. [`docs/PHASE1_REMAINING_DECISIONS.md`](docs/PHASE1_REMAINING_DECISIONS.md) — Structured unresolved Phase 1 decisions.
5. [`audit/phase1-gate.json`](audit/phase1-gate.json) — Machine-readable Phase 1 status.
6. [`audit/phase3-foundation.json`](audit/phase3-foundation.json) — API-independent catalog foundation evidence.
7. [`audit/phase1-partner-research-2026-08-24.md`](audit/phase1-partner-research-2026-08-24.md) — Partner pathway research and limitations.
8. [`STAGING_BLOCKER_REGISTER.md`](STAGING_BLOCKER_REGISTER.md) — External staging blockers.
9. [`STAGING_READINESS_REPORT.md`](STAGING_READINESS_REPORT.md) — Current staging-readiness boundary.
10. [`docs/archive/README.md`](docs/archive/README.md) — Archive policy and legacy-file index.
11. [`PARTNER_INTEGRATION_CONTRACT.md`](PARTNER_INTEGRATION_CONTRACT.md) — Partner mutation contract.
12. [`docs/API_INDEPENDENT_CATALOG_FOUNDATION.md`](docs/API_INDEPENDENT_CATALOG_FOUNDATION.md) — Catalog snapshot and ranking contract.
13. [`docs/RELEASE_OPERATIONS_RUNBOOK.md`](docs/RELEASE_OPERATIONS_RUNBOOK.md) — Release, rollback, and gate procedures.
14. [`audit/documentation-cleanup-2026-08-25.json`](audit/documentation-cleanup-2026-08-25.json) — Documentation reconciliation evidence.

**Document owner:** Manus AI, unless superseded by an explicitly approved owner record.
