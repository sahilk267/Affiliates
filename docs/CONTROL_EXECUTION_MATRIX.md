# ZenithSoles Affiliates — Control Execution Matrix

## Purpose

This matrix prevents local verification from being mistaken for production certification. A control is marked **local** only when it can be exercised against the repository and disposable SQLite/test fixtures without real partner credentials, customer records, provider accounts, or funds. A control is marked **staging** when it requires an environment, external integration, representative data, or an operational owner.

## Credential-free local controls

| Control | Evidence or command | Current status |
|---|---|---|
| Laravel 12 dependency resolution | `composer install` and lockfile | Passed |
| Dependency vulnerability audit | `composer audit --format=plain` | Passed; 0 advisories |
| Release-contract artifacts | `python3 tools/validate_release_contracts.py` | Passed |
| Python tool syntax | `python3 -m py_compile tools/*.py` | Passed |
| PHP syntax | Repository PHP lint command | Passed |
| Clean schema | SQLite `php artisan migrate:fresh --force` | Passed |
| Financial/auth/API/catalog feature suite | PHPUnit | Passed; 15 tests, 61 assertions |
| API route/middleware registration | `php artisan route:list --path=api -v` | Passed |
| Read-only health and latency smoke | `tools/staging_smoke_test.py` | Passed locally; 5/5 health requests |
| Negative partner authentication | `tools/partner_contract_check.py` without mutation flag | Passed locally; invalid and expired signatures rejected |
| Sample payout reconciliation | `tools/reconcile_payouts.py` with sanitized matching exports | Passed; zero exceptions |
| API-independent catalog foundation | `product_price_snapshots` migration, snapshot service, ranking service, and local feature tests | Passed locally; no external partner data used |
| Dynamic repository audit | Build index, generate artifacts, generate score | Passed; current issue inventory and score refreshed |
| CI definition checks | PHP matrix, Composer audit, release-contract validator, diff check | Repository checks passed |

## Staging-only controls

| Control | Why local execution is insufficient | Required evidence |
|---|---|---|
| Valid partner conversion certification | Requires partner key/secret, durable click fixture, webhook contract, and staging data ownership | Signed conversion, idempotent replay, conflict replay, and reconciled identifiers |
| Valid points-credit certification | Mutates a financial ledger and requires an approved partner fixture | One ledger entry, one balance change, idempotent replay, audit log |
| Rate-limit certification | Requires deployed cache/rate-limit backend and realistic partner identity distribution | 429 response, retry behavior, bucket identity, and no bypass through alternate headers |
| Secret-manager injection and rotation | Depends on deployment platform and secret lifecycle controls | Rotation record, old-secret invalidation, no secret leakage in logs |
| Representative production-schema migration rehearsal | SQLite cannot prove MySQL lock/index/constraint behavior or production-scale migration duration | Timed rehearsal, backup/restore checkpoint, schema diff, rollback decision |
| Provider-backed payout reconciliation | Local sample CSVs cannot prove provider references or financial settlement | Platform/provider export comparison with zero unresolved exceptions |
| Payout failure injection | Requires controlled provider failures and operational rollback | Rejection/refund, timeout, duplicate callback, and manual recovery evidence |
| Centralized observability | Requires log shipping, dashboards, alert routes, and on-call ownership | Correlation fields visible centrally; alerts tested and acknowledged |
| Rollback compatibility | Requires the deployed artifact and previous release in the target environment | Rollback rehearsal, schema compatibility, health/smoke result |
| Capacity and performance testing | The bounded smoke harness is not a load test and local SQLite is not production infrastructure | Representative traffic profile, p95/p99, resource limits, saturation behavior, stop condition |
| GeoIP/provider privacy approval | Requires vendor, retention, privacy, and failure-behavior decisions | Approved provider record and data-retention decision |
| License classification | Requires repository-owner or legal decision | Approved SPDX value and license text, or documented internal-use classification |

## Handoff rule

A local pass authorizes the corresponding control to enter the staging checklist; it does not authorize production release. The staging release owner must attach evidence to `FINAL_STAGING_HANDOFF_CHECKLIST.md` and obtain sign-off from security, database, partner integration, payout/reconciliation, and release owners before production deployment.
