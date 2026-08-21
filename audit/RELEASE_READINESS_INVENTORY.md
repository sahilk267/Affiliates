# Release Readiness Inventory

## Dependency audit snapshot

`composer audit --format=json` returned exit code 8 because the installed dependency graph has **20 reported advisories** across the Laravel framework, Symfony components, and `league/commonmark`. The raw result is preserved in `audit/composer-audit-2026-08-20.json`. The advisory set includes high-severity findings affecting Laravel framework, `league/commonmark`, `symfony/http-foundation`, and `symfony/mime`, as well as medium and low findings in related packages.

| Package family | Observed advisory count | Highest observed severity | Release implication |
|---|---:|---|---|
| `laravel/framework` | 3 | High | Upgrade within the supported Laravel 10 constraint or apply the vendor-supported patched release before production |
| `league/commonmark` | 7 | High | Review Markdown parsing exposure and upgrade to a non-affected version |
| `symfony/http-foundation` | 2 | High | Required because request parsing and network protections are framework-adjacent |
| `symfony/mailer` / `symfony/mime` | 3 | High | Required before enabling production email or SMTP flows |
| `symfony/process` | 1 | Medium | Review if process execution is used in deployment or administration |
| `symfony/routing` | 2 | Medium | Review generated URL and route-constraint behavior |
| `symfony/polyfill-intl-idn` | 1 | Low | Upgrade with the Symfony dependency set |
| `league/commonmark` and framework transitive set | 1 additional CVE-class finding | Medium | Confirm lockfile resolution after upgrades |

The audit output should be treated as a release blocker until each advisory is either removed by a tested dependency upgrade or explicitly accepted by the security owner with a documented compensating control. A Composer dry run reports a feasible update set of **44 updates and 1 removal**, including `laravel/framework` 10.49.1 to 10.50.3, `league/commonmark` 2.7.1 to 2.10.0, and patched Symfony 6.4 component releases. The dry-run output is preserved in `audit/composer-update-dry-run-2026-08-20.txt`; no dependency changes were applied without a dedicated upgrade-and-test pass.

## Repository-level gaps that can be addressed without production credentials

| Gap | Safe next action in this repository | Requires external environment |
|---|---|---|
| Dependency advisories | Test a constrained Composer update and rerun the complete suite | Security-owner approval if a major upgrade is proposed |
| Deployment procedure | Add a staging/production runbook with preflight, migration, rollback, and cache steps | Actual deployment credentials and approval |
| Secret handling | Add rotation and validation procedures; keep values out of source | Secret-manager integration and real secret rotation |
| Migration rehearsal | Add a documented representative-schema rehearsal procedure | A sanitized production-schema copy |
| Payout reconciliation | Define a reconciliation file format and control checklist | Real payout-provider exports |
| Performance | Add a safe local/staging smoke harness with bounded concurrency | Staging URL, realistic data, and load-test approval |
| Partner adapters | Define adapter interface and contract fixtures without fake live credentials | Partner APIs, credentials, sandbox certification |
| Observability | Define log fields, retention, alert thresholds, and incident response | Central log/metrics destination |
| GeoIP | Keep null-safe behavior and document provider selection criteria | Provider account, privacy review, retention policy |

## Current verified baseline

The application baseline before the next release-readiness batch is **10 PHPUnit tests with 37 assertions**, clean SQLite migrations, PHP syntax lint passing, API route registration passing, and no whitespace errors from `git diff --check`. The current implementation includes atomic payout orchestration, endpoint-specific throttles, and structured correlation fields in the conversion and points pipeline. `composer validate --strict` returns exit code 1 only because the package metadata has no declared license; this is a metadata decision requiring repository-owner confirmation rather than an assumed legal classification. The validation output is preserved in `audit/composer-validate-2026-08-20.txt`.
