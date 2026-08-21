# ZenithSoles Affiliates — Staging Owner Execution Guide

## Purpose and safety boundary

This guide is for the release owner and designated staging operators. It converts the repository contracts into a controlled execution sequence and defines the evidence required for production approval. It must be run only against an isolated staging deployment with disposable fixtures.

> **Never use a production URL, production credentials, real customer records, or real payout accounts with mutation-enabled certification.**

The local evidence bundle proves code-level controls. This guide covers the external evidence that cannot be established from SQLite, local processes, or sanitized CSVs.

## 1. Assign owners and capture release identity

Before executing tests, record the release commit, deployed version, staging URL, maintenance window, rollback artifact, and named owners in `FINAL_STAGING_HANDOFF_CHECKLIST.md`. The security owner must confirm that staging secrets are injected by the deployment secret manager and are not present in shell history, repository files, CI logs, or application responses.

| Required role | Responsibility |
|---|---|
| Release owner | Coordinates the window, evidence, stop conditions, and final decision. |
| Security owner | Validates HMAC, rate limits, secret rotation, log redaction, and incident controls. |
| Database owner | Validates backup, migration duration, schema compatibility, and restore. |
| Partner integration owner | Runs signed conversion and points-credit certification with the partner fixture. |
| Payout/reconciliation owner | Runs provider-export reconciliation and confirms no funds transfer. |
| Operations owner | Validates centralized logs, alerts, on-call routing, and rollback execution. |

## 2. Run repository and deployment preflight

Run the repository gates on the exact artifact intended for staging. The commands below must be executed in the deployment workspace, not against a developer checkout with uncommitted modifications.

```bash
set -euo pipefail
composer install --no-interaction --no-progress --prefer-dist
composer audit --format=plain
python3 -m py_compile tools/*.py
python3 tools/validate_release_contracts.py
find app bootstrap config database routes tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php artisan route:list --path=api -v
./vendor/bin/phpunit --configuration phpunit.xml.dist --testdox
```

Capture the command output, artifact checksum, PHP version, Composer lockfile hash, and deployed environment identifier. Do not include environment values or secret contents in the evidence bundle.

## 3. Validate secrets and rotation

The staging operator must confirm that `APP_KEY`, database credentials, `AFFILIATE_API_KEY`, `AFFILIATE_API_SECRET`, admin bootstrap values, session configuration, and any provider credentials are injected from the approved secret manager. The value itself must never be copied into the acceptance record.

Perform a controlled rotation as follows:

| Step | Required result |
|---|---|
| Provision a new staging partner secret | New secret is visible only to the secret manager and deployment process. |
| Deploy or reload the application | Application starts without exposing the secret in logs or health responses. |
| Sign a request with the new secret | Request authenticates successfully. |
| Sign a request with the old secret | Request is rejected after the documented overlap window. |
| Search logs and CI artifacts | No secret values or full authorization material are present. |
| Record completion | Owner, timestamp, secret version identifier, and result are captured without the value. |

If rotation requires an overlap window, document the exact start and end timestamps and the old-secret invalidation test.

## 4. Run partner negative certification first

Use the read-only and negative scenarios before enabling any valid financial mutation. The expected results are defined in `PARTNER_INTEGRATION_CONTRACT.md` and `API_SECURITY_CONTRACT.md`.

| Scenario | Expected result | Evidence to capture |
|---|---|---|
| Missing partner headers | `401` | Request ID, status, timestamp, no financial rows |
| Wrong partner key | `401` | Request ID, status, no financial rows |
| Expired timestamp | `401` | Request ID, status, no financial rows |
| Wrong signature | `401` | Request ID, status, no financial rows |
| Signed malformed payload | `422` | Request ID, validation response, no financial rows |
| Invalid click identifier | Validation or conflict response | Request ID, no commission or reward rows |
| Rate threshold exceeded | `429` | Route, identity bucket, count, retry behavior |

The negative suite must pass before the partner owner is allowed to run the valid conversion or points-credit scenarios.

## 5. Run valid conversion certification with disposable fixtures

Create a single disposable staging user, program, link, and click. Record only non-sensitive fixture identifiers. Preserve the exact raw JSON bytes used for HMAC signing and the request timestamp; do not store the secret in the evidence file.

```bash
export STAGING_BASE_URL='https://staging.example.invalid'
export STAGING_PARTNER_KEY='provided-by-secret-manager'
export STAGING_PARTNER_SECRET='provided-by-secret-manager'
export STAGING_CLICK_ID='provided-by-staging-fixture'
export STAGING_EVENT_ID="staging-cert-$(date +%s)"

AFFILIATE_API_KEY="$STAGING_PARTNER_KEY" \
AFFILIATE_API_SECRET="$STAGING_PARTNER_SECRET" \
python3 tools/partner_contract_check.py \
  --base-url "$STAGING_BASE_URL" \
  --allow-mutations \
  --click-id "$STAGING_CLICK_ID" \
  --partner-event-id "$STAGING_EVENT_ID"
unset STAGING_PARTNER_KEY STAGING_PARTNER_SECRET
```

The partner owner and database owner must confirm that the valid request creates exactly one conversion and commission, marks the intended click, and creates only the expected cashback/referral ledger entries. The same payload and `partner_event_id` must be replayed and return an idempotent response without additional rows. A different event ID against the already-converted click must return a conflict without financial side effects.

## 6. Run valid points-credit certification

Use a separate disposable user and a unique idempotency key. The points-credit test must not reuse the conversion fixture or any production-like account. Confirm the starting and ending wallet balance, the single ledger transaction, the reference taxonomy, and the idempotent replay response. Record transaction identifiers, not secret values or complete customer data.

The partner owner must verify that a semantically different operation using the same idempotency key is rejected or does not mutate the wallet. Any balance mismatch is an immediate stop condition.

## 7. Execute payout and reconciliation certification

The payout owner must first confirm that the staging provider account cannot settle real funds. Run approval, cancellation, payment-reference recording, withdrawal, rejection refund, and completion flows against disposable fixtures. Confirm that illegal transitions are rejected and that a rejected redemption produces exactly one idempotent refund.

Export the platform and provider records without bank-account numbers, card data, tokens, or unnecessary customer fields. Run:

```bash
python3 tools/reconcile_payouts.py \
  staging/platform-payout-export.csv \
  staging/provider-payout-export.csv \
  --output staging/payout-reconciliation.json
```

The reconciliation report must show zero unresolved exceptions. Any duplicate, missing provider reference, amount mismatch, status mismatch, or currency mismatch blocks production approval until an owner resolves it and records the decision.

## 8. Rehearse migration, backup, restore, and rollback

The database owner must run migrations against a representative staging schema and data volume, not only an empty SQLite database. Capture migration duration, lock behavior, index creation, foreign-key validation, and schema diff. Take a verified backup before the rehearsal and restore it into an isolated database before accepting the result.

Deploy the prior approved application artifact and perform the rollback procedure. Confirm that the application starts, the health endpoint reports database connectivity, read-only smoke checks pass, and no irreversible schema change prevents rollback. If rollback requires a forward-only migration, document the compensating procedure and obtain database-owner approval.

## 9. Validate observability and incident response

Generate one controlled authentication failure, one validation failure, one rate-limit event, one successful conversion, and one payout exception. The centralized log destination must contain request, click, conversion, partner-event, user, transaction, and idempotency correlation fields where applicable, while redacting secrets and sensitive personal data.

The operations owner must verify alert delivery for authentication spikes, 4xx/5xx spikes, failed rewards, payout exceptions, database failures, and elevated latency. Each alert must have an on-call recipient, runbook link, acknowledgment timestamp, and a documented stop or rollback condition.

## 10. Run capacity testing separately from smoke testing

The bounded smoke harness validates reachability and basic latency only. Capacity testing requires a representative traffic model, realistic concurrency, production-like cache and database services, a defined test duration, resource dashboards, p95/p99 latency, error-rate thresholds, and a stop condition. Do not send mutation traffic at scale unless the staging fixtures and financial side-effect cleanup procedure are approved in writing.

Capture the test command, artifact version, request mix, concurrency, duration, database size, cache configuration, latency percentiles, error rates, CPU, memory, database connections, and queue depth. A passing smoke test is not a capacity certification.

## 11. Evidence and approval

Attach sanitized outputs to `FINAL_STAGING_HANDOFF_CHECKLIST.md`. Every evidence item must contain the artifact version, environment identifier, UTC timestamp, owner, command or scenario, expected result, observed result, and a link to the relevant runbook section. Remove secret values, authorization headers, full customer data, bank data, and provider tokens before attachment.

Production approval is blocked by any failed repository gate, unresolved Composer advisory, failed negative or valid partner test, duplicate financial mutation, payout reconciliation exception, failed restore or rollback, missing central alerting, unassigned on-call coverage, unresolved license decision for external distribution, or any evidence that real funds could be transferred during certification.

## References

1. [`PARTNER_INTEGRATION_CONTRACT.md`](../PARTNER_INTEGRATION_CONTRACT.md), partner authentication, retry, idempotency, and payout contract.
2. [`API_SECURITY_CONTRACT.md`](../API_SECURITY_CONTRACT.md), HMAC signing and mutation security details.
3. [`FINAL_STAGING_HANDOFF_CHECKLIST.md`](../FINAL_STAGING_HANDOFF_CHECKLIST.md), release-owner evidence and sign-off record.
4. [`docs/CONTROL_EXECUTION_MATRIX.md`](CONTROL_EXECUTION_MATRIX.md), local versus staging-only control boundary.
5. [`docs/RELEASE_OPERATIONS_RUNBOOK.md`](RELEASE_OPERATIONS_RUNBOOK.md), deployment, rollback, secret rotation, and incident operations.
6. [`tools/partner_contract_check.py`](../tools/partner_contract_check.py), safe partner certification harness.
7. [`tools/reconcile_payouts.py`](../tools/reconcile_payouts.py), deterministic payout reconciliation checker.
