# ZenithSoles Affiliates — Staging Blocker Register

## Use and ownership

The release owner must assign a named person and due date to every row before the staging window begins. A row may be marked **Closed** only when the linked evidence is sanitized, reproducible, and attached to `FINAL_STAGING_HANDOFF_CHECKLIST.md`. An empty owner, missing evidence, or unresolved stop condition keeps production approval blocked.

| ID | Blocker / control | Owner | Due UTC | Required evidence | Stop condition | Status |
|---|---|---|---|---|---|---|
| STG-001 | Valid partner conversion certification | Partner integration owner |  | Signed request, response, DB identifiers, idempotent replay, conflict replay | Any duplicate conversion, commission, cashback, or referral ledger entry | ☐ Open |
| STG-002 | Valid points-credit certification | Partner integration owner |  | Starting/ending balance, one ledger row, idempotent replay, correlation log | Any balance mismatch or reused key mutating twice | ☐ Open |
| STG-003 | Endpoint rate-limit certification | Security owner |  | Request counts, identity bucket, HTTP 429, retry behavior, alternate-header check | Limit bypass, missing bucket isolation, or unsafe retry behavior | ☐ Open |
| STG-004 | Secret-manager injection and rotation | Security owner |  | Secret version IDs, old-secret rejection, log-redaction review | Secret appears in source, logs, CI output, or old credential remains valid | ☐ Open |
| STG-005 | Representative-schema migration rehearsal | Database owner |  | Backup ID, schema diff, duration, lock/constraint observations | Failed migration, unacceptable lock window, or schema drift | ☐ Open |
| STG-006 | Backup and restore rehearsal | Database owner |  | Backup checksum, restore target, row/count checks, timestamp | Restore cannot be verified or data integrity differs | ☐ Open |
| STG-007 | Application rollback rehearsal | Release owner |  | Previous artifact, rollback log, health/smoke output, schema decision | Rollback fails to start, loses compatibility, or requires unapproved data repair | ☐ Open |
| STG-008 | Provider-backed payout reconciliation | Payout/reconciliation owner |  | Sanitized exports, machine-readable report, exception owner list | Any unresolved duplicate, amount, status, currency, or reference mismatch | ☐ Open |
| STG-009 | Payout failure and refund injection | Payout/reconciliation owner |  | Rejection, timeout, duplicate callback, exactly-once refund evidence | Double refund, unreconciled redemption, or provider side effect | ☐ Open |
| STG-010 | Centralized logging and alert delivery | Operations owner |  | Correlation fields, redaction review, alert receipt/acknowledgment | Missing financial correlation, secret leakage, or unowned alert | ☐ Open |
| STG-011 | On-call and incident-response readiness | Operations owner |  | Escalation roster, incident drill record, runbook link | No acknowledged on-call path or unclear rollback authority | ☐ Open |
| STG-012 | Capacity and performance certification | Operations owner |  | Traffic model, p95/p99, error rate, resource graphs, stop condition | Threshold breach, saturation, or no reliable measurement | ☐ Open |
| STG-013 | GeoIP/provider privacy approval | Security/privacy owner |  | Vendor decision, retention period, fallback behavior, approval record | Provider lacks approval or failure path fabricates location | ☐ Open |
| STG-014 | Repository license classification | Repository owner / legal owner |  | Approved SPDX/license text or internal-use decision | External distribution planned without an approved classification | ☐ Open |

## Evidence naming convention

Use UTC timestamps and the release commit in every filename. Do not include secrets, authorization headers, bank data, full customer records, or provider tokens.

```text
staging-evidence/<release-commit>/<UTC>-<blocker-id>-<short-description>.<ext>
```

Examples include `2026-08-21T120000Z-STG-008-payout-reconciliation.json` and `2026-08-21T123000Z-STG-010-alert-redaction-review.md`.

## Approval rule

The release owner may request production approval only when all P0/P1 rows are **Closed**, all evidence links resolve, all stop conditions are explicitly cleared by the responsible owner, and the sign-off table in `FINAL_STAGING_HANDOFF_CHECKLIST.md` is complete. A local test pass does not close a staging-only row.

## References

1. [`FINAL_STAGING_HANDOFF_CHECKLIST.md`](FINAL_STAGING_HANDOFF_CHECKLIST.md), staging gates and sign-off.
2. [`docs/STAGING_OWNER_EXECUTION_GUIDE.md`](docs/STAGING_OWNER_EXECUTION_GUIDE.md), execution sequence and evidence handling.
3. [`docs/CONTROL_EXECUTION_MATRIX.md`](docs/CONTROL_EXECUTION_MATRIX.md), local versus staging-only boundary.
4. [`docs/RELEASE_OPERATIONS_RUNBOOK.md`](docs/RELEASE_OPERATIONS_RUNBOOK.md), operational procedures and incident response.
