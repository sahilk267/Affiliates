# ZenithSoles Affiliates — Final Staging Handoff Checklist

## Release identity

The authoritative external-blocker register is `STAGING_BLOCKER_REGISTER.md`. Assign every owner and due date before the staging window; do not mark a control complete without sanitized evidence linked to its blocker row.

| Field | Value |
|---|---|
| Release commit |  |
| Staging URL |  |
| Deployment version |  |
| Release owner |  |
| Security owner |  |
| Database owner |  |
| Partner integration owner |  |
| Payout/reconciliation owner |  |
| Planned maintenance window UTC |  |
| Rollback target |  |

## Repository gates

| Gate | Required command or evidence | Status |
|---|---|---|
| PHP compatibility | PHP 8.2+ on the target runtime | ☐ |
| Dependency installation | `composer install --no-interaction --no-progress --prefer-dist` | ☐ |
| Dependency audit | `composer audit --format=plain` with no advisories | ☐ |
| Contract validation | `python3 tools/validate_release_contracts.py` | ☐ |
| Tool compilation | `python3 -m py_compile tools/*.py` | ☐ |
| PHP lint | Repository PHP lint command | ☐ |
| Clean migration | `php artisan migrate:fresh --force` on a sanitized staging database | ☐ |
| Feature tests | `./vendor/bin/phpunit --configuration phpunit.xml.dist --testdox` | ☐ |
| API route inspection | `php artisan route:list --path=api -v` | ☐ |
| Read-only smoke | `python3 tools/staging_smoke_test.py` | ☐ |
| Dynamic audit | `python3 tools/build_audit_index.py && python3 tools/generate_audit_artifacts.py && python3 tools/generate_release_score.py` | ☐ |
| Diff integrity | `git diff --check` | ☐ |

## Partner certification

The read-only checks must be completed before any valid mutation test. Valid mutation certification must use only disposable staging fixtures and credentials stored in the staging secret manager.

| Scenario | Expected evidence | Status |
|---|---|---|
| Health and database connectivity | HTTP 200 and `database: connected` | ☐ |
| Invalid HMAC | HTTP 401 and no financial rows created | ☐ |
| Expired timestamp | HTTP 401 and no financial rows created | ☐ |
| Signed malformed conversion | HTTP 422 and no financial rows created | ☐ |
| Valid staging conversion | One conversion, one commission, expected reward ledger rows | ☐ |
| Idempotent conversion replay | Same conversion returned; no duplicate financial records | ☐ |
| Conflicting event on converted click | HTTP 409; no second conversion | ☐ |
| Points credit replay | One ledger entry and one balance change | ☐ |
| Rate limit | HTTP 429 after configured threshold; Retry-After honored where supplied | ☐ |
| Partner reconciliation | Event, click, conversion, commission, and ledger identifiers reconciled | ☐ |

Use the checker only as follows for an approved staging mutation window:

```bash
AFFILIATE_API_KEY="$STAGING_AFFILIATE_API_KEY" \
AFFILIATE_API_SECRET="$STAGING_AFFILIATE_API_SECRET" \
python3 tools/partner_contract_check.py \
  --base-url "$STAGING_BASE_URL" \
  --allow-mutations \
  --click-id "$STAGING_CLICK_ID" \
  --partner-event-id "staging-cert-<unique-id>"
```

Never pass production credentials to local or developer environments. Never use a production URL with `--allow-mutations`.

## Payout and database controls

| Control | Status |
|---|---|
| Representative production-like migration rehearsal completed | ☐ |
| Backup/restore rehearsal completed | ☐ |
| Pending commission approval and cancellation tested | ☐ |
| Commission payment requires approval and records provider reference | ☐ |
| Withdrawal debit and redemption creation are atomic | ☐ |
| Rejected redemption refunds exactly once | ☐ |
| Completed redemption records actor, method, reference, and timestamp | ☐ |
| Platform/provider payout exports reconcile with zero unresolved exceptions | ☐ |
| No real funds transferred during certification | ☐ |

Run the checker against approved staging exports:

```bash
python3 tools/reconcile_payouts.py \
  staging/platform-payout-export.csv \
  staging/provider-payout-export.csv \
  --output staging/payout-reconciliation.json
```

## Deployment and operations

| Control | Status |
|---|---|
| Secret manager injects all required production values | ☐ |
| Secret rotation procedure tested without downtime or credential leakage | ☐ |
| Centralized logs receive request and financial correlation identifiers | ☐ |
| Alerts configured for authentication failures, 4xx/5xx spikes, reward failures, and payout exceptions | ☐ |
| Queue/worker posture explicitly decided for the deployment | ☐ |
| Rollback artifact is deployable and schema-compatible | ☐ |
| Incident commander and on-call coverage assigned | ☐ |
| Data retention and GeoIP privacy decisions approved | ☐ |
| License decision recorded by repository owner | ☐ |

## Approval rule

The release owner must reconcile this checklist with `STAGING_BLOCKER_REGISTER.md`. Every staging-only control must have a named owner, evidence link, and cleared stop condition before production approval.


Production deployment is blocked by any failed repository gate, unresolved dependency advisory, failed staging migration, failed partner certification, unresolved payout reconciliation exception, untested rollback target, missing secret validation, absent monitoring/on-call coverage, or pending legal/license decision where external distribution is intended.

## Sign-off

| Role | Name | Decision | Timestamp UTC |
|---|---|---|---|
| Release owner |  | ☐ Approve ☐ Block |  |
| Security owner |  | ☐ Approve ☐ Block |  |
| Database owner |  | ☐ Approve ☐ Block |  |
| Partner integration owner |  | ☐ Approve ☐ Block |  |
| Payout/reconciliation owner |  | ☐ Approve ☐ Block |  |
