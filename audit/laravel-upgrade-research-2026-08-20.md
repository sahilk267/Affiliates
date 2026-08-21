# Laravel Upgrade Research Notes — 2026-08-20

## Official guidance reviewed

The official Laravel 12 upgrade guide states that upgrading from Laravel 11 to Laravel 12 requires updating `laravel/framework` to `^12.0`; it also lists PHPUnit 11 and Pest 3 updates where applicable. It identifies Carbon 3 as a compatibility requirement because Carbon 2 support is removed in Laravel 12. The guide presents Laravel 12 as a major-version upgrade with documented high-, medium-, and low-impact changes.

The official Laravel 12 release notes state that Laravel releases receive bug fixes for 18 months and security fixes for two years. The published support table lists Laravel 10 security fixes through **February 4, 2025**, Laravel 11 through **March 12, 2026**, and Laravel 12 through **February 24, 2027**. Laravel 12 requires PHP 8.2–8.5, while the project currently declares PHP `^8.1` and the sandbox is running PHP 8.3.6.

## Implication for ZenithSoles Affiliates

The current Laravel 10 application is beyond the official security-support date shown in the Laravel 12 release notes. The residual Composer audit findings affecting the Laravel 10 major line therefore represent a genuine release blocker rather than a dependency-noise issue. A Laravel 12 migration spike is the appropriate next engineering path; a Laravel 10 exception should be treated as temporary and require named security-owner approval, compensating controls, and an expiry date.

## Sources

1. [Laravel 12 Upgrade Guide](https://laravel.com/framework/docs/12.x/upgrade)
2. [Laravel 12 Release Notes and Support Policy](https://laravel.com/framework/docs/12.x/releases)
3. [Laravel Framework Security Advisory: CRLF injection in default email rule](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq)
