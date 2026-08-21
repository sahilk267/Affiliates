# Laravel 12 Upgrade Report

## Executive result

The Laravel 12 upgrade path was evaluated in an isolated spike, passed the complete existing release-blocking test suite, and was then applied to the working repository. The application now targets PHP `^8.2`, Laravel `^12.0`, and PHPUnit `^11.0`. The resolved framework version is Laravel **12.67.0** and the resolved PHPUnit version is **11.5.56**.

The post-upgrade Composer audit reports **no security vulnerability advisories**. The upgrade therefore removes the residual Laravel framework advisory blocker identified in the previous readiness batch.

## Why the upgrade was required

Laravel’s official support policy provides 18 months of bug fixes and two years of security fixes for each Laravel release. The official support table lists Laravel 10 security fixes through February 4, 2025, Laravel 11 through March 12, 2026, and Laravel 12 through February 24, 2027.[1] The project’s previous Laravel 10 dependency line was therefore beyond the published security-support window at the current task date.

The official Laravel 12 upgrade guide requires `laravel/framework` `^12.0`, PHPUnit 11 where PHPUnit is used, and Carbon 3 because Carbon 2 support was removed.[2] The isolated upgrade spike resolved these dependencies successfully and exposed no code-level incompatibilities in the current feature suite.

## Applied dependency changes

| Dependency or platform requirement | Previous constraint/resolution | Current constraint/resolution |
|---|---|---|
| PHP | `^8.1` | `^8.2` |
| `laravel/framework` | `^10.0`, resolved 10.50.3 | `^12.0`, resolved 12.67.0 |
| `phpunit/phpunit` | `^10.5`, resolved 10.5.64 | `^11.0`, resolved 11.5.56 |
| Carbon | 2.x transitive line | 3.13.2 |
| Symfony components | 6.4 transitive line | 7.4 transitive line where required by Laravel 12 |

The lockfile was regenerated with `composer update --with-all-dependencies --no-interaction`. No application source changes were needed for the tested path beyond the dependency and PHP constraints.

## Verification evidence

| Gate | Result | Evidence |
|---|---|---|
| Isolated Laravel 12 dependency resolution | Passed | Spike resolved Laravel 12.67.0 and PHPUnit 11.5.56 |
| Composer audit after upgrade | Passed; no advisories | `audit/composer-audit-laravel12-2026-08-20.json` |
| PHP syntax lint | Passed | Regression command across application, migrations, routes, configuration, bootstrap, and tests |
| Clean SQLite migrations | Passed | `audit/laravel12-migrate-2026-08-20.txt` |
| PHPUnit | **10 tests, 37 assertions, all passing** | `audit/laravel12-phpunit-2026-08-20.txt` |
| API route registration | Passed | `audit/laravel12-routes-2026-08-20.txt` |
| Read-only smoke test | Passed: 5/5 health requests successful | `audit/laravel12-smoke-2026-08-20.txt` |

The smoke harness remained mutation-safe by default. No real partner credentials, live payout operations, or production systems were used.

## Operational implications

The deployment environment must now provide PHP 8.2 or newer. The staging migration rehearsal, partner webhook certification, payout reconciliation dry run, secret-manager validation, and centralized observability requirements from `docs/RELEASE_OPERATIONS_RUNBOOK.md` remain mandatory. The upgrade removes the Composer advisory blocker but does not substitute for those environment-specific controls.

The Laravel 12 upgrade should be promoted through staging as a normal major-version release. The release owner should compare authentication, route middleware, validation, mail configuration, cache behavior, session behavior, queue workers, and admin payout workflows against the previous staging baseline before production approval.

## References

[1]: https://laravel.com/framework/docs/12.x/releases "Laravel 12 Release Notes and Support Policy"
[2]: https://laravel.com/framework/docs/12.x/upgrade "Laravel 12 Upgrade Guide"
[3]: https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq "Laravel Framework CRLF Injection Advisory"
