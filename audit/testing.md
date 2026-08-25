# Testing Audit

The current repository verification is reproducible locally with disposable SQLite data and no external partner credentials.

| Metric | Measured result | Evidence |
|---|---:|---|
| PHPUnit test files | 2 | `tests/Feature/ReleaseBlockingControlsTest.php`, `tests/Feature/CatalogFoundationTest.php` |
| PHPUnit tests | 15 | Latest no-coverage PHPUnit run |
| PHPUnit assertions | 61 | Latest no-coverage PHPUnit run |
| Python guardrail tests | 4 | `tools/test_validate_pilot_decision_inputs.py` |
| Clean SQLite migration | PASS | `php artisan migrate:fresh --force` |
| PHP lint | PASS | Current application, migration, route, and test sources |
| Release-contract validation | PASS; 29 required files | `python3 tools/validate_release_contracts.py` |
| Pilot report-only validator | BLOCKED as expected | 32 required fields and 4 owner sign-offs remain |
| Pilot strict validator | Expected non-zero exit | `--require-approved` intentionally enforces the Phase 1 gate |
| Coverage percentage | NOT MEASURABLE locally | No local coverage driver was available for the verified run |
| Production load behavior | NOT MEASURABLE | Requires representative staging infrastructure and monitoring |

The local suite covers authentication, authorization, signed partner mutations, idempotency, payout/refund state transitions, security headers, catalog snapshots, price-history access, input validation, and deterministic ranking primitives. Passing local fixtures do not prove partner API availability, merchant attribution, settlement, voucher delivery, or production capacity. CI remains the authoritative coverage-enabled environment where PCOV is available.
