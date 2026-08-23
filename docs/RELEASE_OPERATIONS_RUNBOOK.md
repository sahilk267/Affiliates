# ZenithSoles Affiliates Release Operations Runbook

## Purpose and release boundary

This runbook is the operational control for staging and production releases of ZenithSoles Affiliates. It assumes that the application code has passed the repository verification gates and that the release owner has completed partner, payout, security, and privacy approvals. It deliberately does not contain credentials, provider tokens, customer data, or production connection strings.

A release is not production-ready merely because the local PHPUnit suite passes. Production approval also requires a successful dependency-advisory review, representative-schema migration rehearsal, secret validation, partner webhook certification, payout reconciliation readiness, centralized log delivery, and a rollback decision owner.

## Required environment variables

| Variable | Required in staging | Required in production | Validation |
|---|---|---|---|
| `APP_KEY` | Yes | Yes | Must be non-empty and managed by the deployment secret store |
| `APP_ENV` | `staging` | `production` | Must match the target environment |
| `APP_DEBUG` | `false` | `false` | Deployment must fail if enabled outside local development |
| `DB_CONNECTION` and database credentials | Yes | Yes | Connection tested using the deployment identity |
| `AFFILIATE_API_KEY` | Yes | Yes | Non-empty, partner-specific, and rotatable |
| `AFFILIATE_API_SECRET` | Yes | Yes | Non-empty, partner-specific, and rotatable |
| `ADMIN_EMAIL` | Yes | Yes | Approved administrator mailbox |
| `ADMIN_PASSWORD` | Seeder only | Seeder only | Injected only for controlled seeding; never committed |
| `LOG_CHANNEL` | `single` or centralized driver | Centralized driver | Log delivery and retention verified |

The release pipeline must source these values from the environment's secret manager. The `.env.example` file is a template only and must never be copied into a live environment without replacing every secret placeholder.

## 1. Preflight checklist

The release owner should begin by recording the commit SHA, deployment version, database schema version, dependency audit result, test artifact location, and rollback target. The working tree used to build the release must be clean, and generated deployment caches must not contain values from another environment.

```bash
git rev-parse HEAD
git diff --check
composer validate --strict
composer audit --format=json
find app database routes config bootstrap tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
./vendor/bin/phpunit --configuration phpunit.xml.dist --testdox
```

A non-zero result from `composer audit` is a release blocker unless the security owner has recorded an advisory-by-advisory exception with affected code paths, compensating controls, expiry date, and named approver.

The deployment pipeline must also verify that the application can connect to the target database, that the configured affiliate key and secret are non-empty, that debug mode is disabled, and that the health endpoint returns a database-backed healthy response after deployment. Web and API responses must include the baseline security headers asserted by the feature suite: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, and the documented `Permissions-Policy`. HSTS is enabled only for secure production requests.

## 2. Migration rehearsal and deployment

Migration rehearsal must use a sanitized database copy that preserves representative table sizes, indexes, foreign keys, and legacy columns. The rehearsal must run on the same database engine and major version as production. Record start time, end time, lock duration, migration output, row counts for affected tables, and rollback instructions.

The standard sequence is:

```bash
php artisan down --render="errors::503" --secret="$MAINTENANCE_BYPASS_SECRET"
php artisan migrate:status
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

For zero-downtime deployments, the release must preserve backward compatibility between the currently running application and the new schema during the migration window. Destructive column removals, enum narrowing, and data transformations require a separate expand–migrate–contract release sequence. Never run `migrate:fresh` against staging or production.

After deployment, verify the health route, login/logout, partner signature rejection, signed conversion replay, points-credit replay, withdrawal creation, and admin payout state transitions using staging fixtures. Do not execute real payouts during smoke tests.

## 3. Secret rotation procedure

Secret rotation must be staged so that the old and new values do not cause an uncontrolled outage. For partner authentication, provision the new key and secret in the partner's secure environment, deploy the platform's matching values during an agreed maintenance window, run a signed staging request, and then revoke the old credentials. Record who approved the rotation, when the old values were disabled, and which partner event IDs were used for verification.

The admin credential rotation must be performed through the secret manager and the approved administrative procedure. The database must not receive a password value through a shell history, issue tracker, or committed migration. After rotation, verify a successful login with the new credential, session regeneration, logout invalidation, and failed authentication with the old credential.

If a secret is exposed, immediately disable it, preserve relevant audit logs, rotate the replacement, review recent requests for misuse, and document the incident. Never place the exposed value in this runbook or in a remediation commit.

## 4. Partner webhook and conversion certification

Each partner must certify the exact raw-body HMAC implementation, timestamp window, retry behavior, and idempotency behavior in staging. Certification should include a valid conversion, an invalid signature, an expired timestamp, a validation failure, a repeated `partner_event_id`, and a different event targeting an already converted click.

The certification record must reconcile the following identifiers from request through ledger: `partner_event_id`, `click_id`, `conversion_id`, `commission_id`, cashback `points_transaction_id`, referral `points_transaction_id`, and any partner order ID. The partner must confirm that a successful idempotent replay does not create a second financial record.

## 5. Payout and reconciliation controls

Before enabling payouts, the operations owner must confirm that commission approval, cancellation, payment, redemption approval, rejection refund, and completion are restricted to authorized administrators and produce auditable actor and reference fields. A payout batch must have a deterministic batch identifier and an immutable source export.

The minimum reconciliation file should contain one row per platform financial record:

| Field | Description |
|---|---|
| `batch_id` | Immutable payout or reconciliation batch identifier |
| `platform_type` | `commission` or `redemption` |
| `platform_id` | Internal commission or redemption identifier |
| `user_id` | Beneficiary identifier |
| `amount_or_points` | Monetary amount or points amount |
| `status` | Platform status at export time |
| `external_reference` | Provider transaction or settlement reference |
| `processed_at` | UTC processing timestamp |
| `reconciled_at` | UTC reconciliation timestamp |
| `exception_code` | Stable reason for any mismatch |

Reconciliation must compare the platform export with the provider settlement report, identify missing, duplicate, amount-mismatched, and status-mismatched rows, and assign every exception to an owner. Unresolved financial exceptions block the next payout batch.

## 6. Rollback and incident response

Rollback is appropriate for failed health checks, schema incompatibility, authentication outages, duplicate financial records, unrecoverable partner signature failures, or unacceptable error rates. The incident commander must stop new payout batches before rollback and preserve request, conversion, ledger, and payout identifiers for investigation.

Application rollback is safe only when the database remains compatible with the previous application version. If a migration has changed data shape, use the rehearsed backward-compatible recovery procedure rather than an untested down migration. Never delete financial records to repair a duplicate; use an auditable compensating transaction and preserve the original records.

After recovery, rerun the health and smoke checks, compare conversion and points ledger counts before and after the incident window, validate partner retry queues, and publish an incident summary with root cause, impact, corrective action, and follow-up owner.

## 7. Staging smoke and bounded performance check

The repository includes `tools/staging_smoke_test.py` and `tools/partner_contract_check.py`. By default they perform health checks and expected-rejection checks without financial mutations. The deployment evidence template is `docs/STAGING_ACCEPTANCE_RECORD.md`, and payout comparisons are performed by `tools/reconcile_payouts.py`.

The repository includes `tools/staging_smoke_test.py`. By default it performs a health check and a bounded read-only latency sample. It must be run against staging only, with an approved request count and concurrency limit:

```bash
python3 tools/staging_smoke_test.py \
  --base-url "$STAGING_BASE_URL" \
  --requests 20 \
  --concurrency 4 \
  --timeout 5

python3 tools/partner_contract_check.py \
  --base-url "$STAGING_BASE_URL" \
  --timeout 5
```

The tool refuses more than 200 requests or 10 concurrent workers and does not submit financial mutations unless `--allow-mutations` is explicitly provided. An approved staging conversion check requires `AFFILIATE_API_KEY`, `AFFILIATE_API_SECRET`, a dedicated staging `click_id`, and a unique `partner_event_id`; it must never be pointed at production. Record the health response, success rate, latency summary, and any HTTP errors in the release artifact.

This harness is a smoke and regression tool, not a capacity certification. Formal load testing requires staging approval, representative data, a defined traffic model, monitoring, and an agreed stop condition.

Before a staging window, quantify handoff completeness with the report-only blocker check:

```bash
python3 tools/validate_staging_blockers.py
```

The release owner must run the strict gate only after named owners, due dates, sanitized evidence, cleared stop conditions, and closed statuses are populated:

```bash
python3 tools/validate_staging_blockers.py --require-ready
```

A non-zero strict-gate result blocks production approval. The authoritative register is `STAGING_BLOCKER_REGISTER.md`.

Before Phase 1 business implementation, validate the owner decision template in report-only mode:

```bash
python3 tools/validate_pilot_decision_inputs.py
```

This must report the current missing fields, selected pilot model, and owner sign-off state. The strict gate is intentionally separate because the repository template starts incomplete:

```bash
python3 tools/validate_pilot_decision_inputs.py --require-approved
```

The strict command must be used only in an approved pilot-release workflow. CI runs the report-only check on every build and can enforce the strict check when the repository variable `ENFORCE_PILOT_DECISION=true` is explicitly enabled. A non-zero strict result means Phase 1 is blocked; the agent must request owner inputs rather than selecting a niche, audience, business model, partner, rate, or approval on its own.

## 8. Observability and alerting

Centralized logs should retain the structured fields emitted by the application, especially `request_id`, `partner_event_id`, `click_id`, `conversion_id`, `user_id`, `order_id`, `transaction_id`, `reference_id`, and idempotency keys. Alerting should cover authentication failures, rate-limit spikes, conversion processing failures, reward credit failures, wallet debit failures, payout state-transition errors, and reconciliation exceptions.

At minimum, the on-call dashboard should show request volume, HTTP 4xx/5xx rates, partner signature failures, conversion success and conflict counts, reward failures, pending commissions, pending redemptions, refund counts, and unreconciled payout rows. Thresholds must be calibrated using staging and early production baselines rather than copied from this document.

## 9. Release sign-off

The release owner, security owner, database owner, payout owner, and partner integration owner must sign off the controls relevant to their area. The release record should link to the dependency audit output, migration rehearsal output, test report, partner certification, reconciliation dry run, deployment logs, and rollback target.

A release may proceed to production only when no unowned critical or high-severity dependency advisory remains, all environment secrets are validated, migrations have been rehearsed, partner certification is complete, payout reconciliation is operational, and an on-call owner is assigned.
