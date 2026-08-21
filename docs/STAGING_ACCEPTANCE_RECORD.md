# ZenithSoles Affiliates — Staging Acceptance Record

> This template is completed by the release owner in staging. Do not paste secrets, raw customer data, bank details, or production payout references into this file.

## Release identity

| Field | Value |
|---|---|
| Release version / commit SHA |  |
| Staging environment |  |
| PHP version |  |
| Laravel version |  |
| Database engine and version |  |
| Deployment start UTC |  |
| Deployment end UTC |  |
| Rollback target |  |
| Incident/on-call owner |  |

## Automated gates

| Gate | Command or artifact | Result | Evidence link | Owner |
|---|---|---|---|---|
| Composer validation | `composer validate --strict` | ☐ Pass ☐ Fail ☐ Exception |  |  |
| Composer security audit | `composer audit --format=json` | ☐ Pass ☐ Fail ☐ Exception |  |  |
| PHP lint | Repository PHP lint command | ☐ Pass ☐ Fail |  |  |
| Database migration rehearsal | Sanitized production-like schema | ☐ Pass ☐ Fail |  |  |
| PHPUnit | `./vendor/bin/phpunit --configuration phpunit.xml.dist --testdox` | ☐ Pass ☐ Fail |  |  |
| Read-only smoke | `tools/staging_smoke_test.py` | ☐ Pass ☐ Fail |  |  |
| Route/middleware inspection | `php artisan route:list --path=api -v` | ☐ Pass ☐ Fail |  |  |

## Partner certification

| Scenario | Expected result | Actual result | Evidence |
|---|---|---|---|
| Valid health request | HTTP 200 and database connected |  |  |
| Invalid HMAC signature | HTTP 401; no mutation |  |  |
| Expired timestamp | HTTP 401; no mutation |  |  |
| Signed malformed conversion | HTTP 422 or configured unavailable response; no financial record |  |  |
| Valid conversion | HTTP 200; conversion and commission created once |  |  |
| Same `partner_event_id` replay | HTTP 200 with idempotent response; no duplicate financial rows |  |  |
| New event against converted click | HTTP 409; no second conversion |  |  |
| Points-credit replay | Original transaction returned; balance changes once |  |  |
| Rate-limit behavior | HTTP 429 after configured threshold |  |  |

The valid mutation rows require dedicated staging fixtures and approved staging credentials. The partner must reconcile `partner_event_id`, `click_id`, `conversion_id`, commission ID, points transaction IDs, and order ID without including sensitive customer values in this record.

## Payout and reconciliation certification

| Control | Result | Evidence |
|---|---|---|
| Pending commission approval guard | ☐ Pass ☐ Fail |  |
| Commission cannot be paid before approval | ☐ Pass ☐ Fail |  |
| Pending redemption approval guard | ☐ Pass ☐ Fail |  |
| Rejection refund is idempotent | ☐ Pass ☐ Fail |  |
| Redemption completion records provider reference | ☐ Pass ☐ Fail |  |
| Platform/provider CSV reconciliation | ☐ Matched ☐ Exceptions |  |
| All exceptions assigned an owner | ☐ Yes ☐ No |  |
| No real funds transferred during certification | ☐ Confirmed |  |

Run the reconciliation checker as follows:

```bash
python3 tools/reconcile_payouts.py \
  staging/platform-payout-export.csv \
  staging/provider-payout-export.csv \
  --output staging/payout-reconciliation.json
```

The checker exits with status 0 only when schemas are valid and no missing, unexpected, duplicate, amount, status, or provider-reference mismatches are found. An exception report must block the payout batch until each exception is investigated and resolved.

## Observability and rollback

| Check | Result | Evidence |
|---|---|---|
| Central logs receive `request_id` and financial correlation fields | ☐ Pass ☐ Fail |  |
| Alerts exist for 4xx/5xx, signature failures, reward failures, and payout exceptions | ☐ Pass ☐ Fail |  |
| Rollback target is deployable | ☐ Pass ☐ Fail |  |
| Previous application remains schema-compatible | ☐ Pass ☐ Fail |  |
| Incident commander and on-call owner assigned | ☐ Yes ☐ No |  |

## Sign-off

| Role | Name | Decision | Timestamp UTC |
|---|---|---|---|
| Release owner |  | ☐ Approve ☐ Block |  |
| Security owner |  | ☐ Approve ☐ Block |  |
| Database owner |  | ☐ Approve ☐ Block |  |
| Partner integration owner |  | ☐ Approve ☐ Block |  |
| Payout/reconciliation owner |  | ☐ Approve ☐ Block |  |

Production approval is blocked by any unresolved critical or high-severity dependency finding, failed migration rehearsal, failed partner certification, unresolved payout exception, missing secret validation, absent rollback target, or unassigned on-call owner.
