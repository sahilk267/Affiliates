# Release Readiness Inventory

**Updated:** 2026-08-25
**Decision:** Suitable for continued local development and controlled staging preparation; **not approved for production**.

## Current repository baseline

| Control | Current result | Evidence |
|---|---|---|
| PHP constraint | `^8.2` | `composer.json` |
| Laravel constraint/resolution | `^12.0` / 12.67.0 | `composer.json`, `composer.lock` |
| PHPUnit constraint/resolution | `^11.0` / 11.5.56 | `composer.json`, `composer.lock` |
| Composer strict validation | PASS | `composer validate --strict` |
| Composer security audit | PASS; 0 advisories | `composer audit --format=plain` |
| Clean SQLite migration | PASS | Disposable local test database |
| PHPUnit | PASS; 19 tests and 91 assertions | `phpunit.xml.dist`, latest no-coverage run |
| Python guardrail tests | PASS; 4 tests | `tools/test_validate_pilot_decision_inputs.py` |
| Release-contract inventory | PASS; 34 required files | `tools/validate_release_contracts.py` |
| API-independent catalog foundation | Implemented locally | `audit/phase3-foundation.json` |
| Production approval | Not approved | Staging acceptance and blocker records |

Dated Composer and PHPUnit files from the pre-upgrade audit remain historical evidence. They must not be read as current dependency or test results unless their generated date and commit are explicitly checked.

## Safe repository work completed

| Workstream | Current status | Evidence or boundary |
|---|---|---|
| Security and authentication | Implemented and locally tested | HMAC partner mutation protection, Laravel guard authentication, ownership checks, throttles, headers, and idempotency |
| Financial state transitions | Implemented and locally tested | Transaction-scoped services, locking, deterministic idempotency, and audit logging |
| Catalog foundation | Implemented without external APIs | Snapshot-backed price-first comparison preview, labelled synthetic fixtures, nullable unknown fields, history queries, generic adapter boundary, and explicit ranking primitives; comparison rewards/vouchers/gifts remain disabled |
| Partner/API research | Documented, not activated | `audit/phase1-partner-research-2026-08-24.md` |
| Pilot decision gate | Blocked by owner inputs | `audit/phase1-gate.json` and `docs/PHASE1_REMAINING_DECISIONS.md` |
| Documentation cleanup | Active/archived sources separated | `README.md` and `docs/archive/README.md` |

## External blockers before staging certification

| Blocker | Required evidence | Current status |
|---|---|---|
| Partner/API access | Current partner or network acceptance, technical documentation, secure credentials, and approved campaign status | Pending |
| Product/price data rights | Permission for product, price, availability, rating, demand, and price-history fields and retention | Pending |
| Direct/intermediary route | Selected route for each first 3–5 partner targets, with campaign approval | Pending |
| Attribution certification | Disposable staging click, valid conversion, replay/conflict checks, and reconciled partner identifiers | Pending |
| Reward policy | Approved points, voucher, confirmation, reversal, deduction, fraud, and gift rules | Pending |
| MySQL migration rehearsal | Timed migration, schema comparison, backup/restore checkpoint, and rollback decision | Pending |
| Secret lifecycle | Secret-manager injection, rotation, old-secret invalidation, and log-redaction evidence | Pending |
| Payout/reconciliation | Provider-backed export comparison and zero unresolved exceptions | Pending |
| Observability | Central logs, dashboards, alerts, acknowledgement, and on-call ownership | Pending |
| Capacity | Representative traffic profile, p95/p99, saturation behavior, and stop condition | Pending |
| Privacy and legal | Data-retention, vendor, affiliate disclosure, licensing, and partner-terms decisions | Pending |
| Named owners and dates | Product, Release, Affiliate Integration, Data/Privacy, Finance/Payout, Security, Engineering, and Operations owners | Pending |

## Phase gate status

Phase 1 remains blocked because the owner has not yet supplied all required category, measurable audience, partner approval, data-permission, ranking, reward/voucher/reversal/gift, metric, owner, date, and staging inputs. The selected direction is recorded as **consumer affiliate comparison with post-confirmation reward points**, but selection is not the same as full approval.

Phase 3 is complete for the API-independent foundation only. It does not authorize a merchant adapter, scraping, a live price claim, a live commission claim, a voucher issuance, a gift commitment, a staging mutation, or production release.

## Required handoff evidence

Use the following active documents for the next release decision:

- `docs/OWNER_ACTION_PACKAGE.md`
- `audit/owner-action-package.json`
- `docs/PILOT_DECISION_INPUT_TEMPLATE.md`
- `docs/PHASE1_REMAINING_DECISIONS.md`
- `docs/PHASE1_OWNER_AND_TIMELINE_PROPOSAL.md`
- `STAGING_BLOCKER_REGISTER.md`
- `STAGING_READINESS_REPORT.md`
- `docs/RELEASE_OPERATIONS_RUNBOOK.md`
- `docs/STAGING_ACCEPTANCE_RECORD.md`
- `FINAL_STAGING_HANDOFF_CHECKLIST.md`

No real funds, production credentials, production customer data, or production mutation should be used to close any of these gates.
