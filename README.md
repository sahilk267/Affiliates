# ZenithSoles Affiliate Management System

A scalable, consumer-facing cashback and referral platform with points-based rewards system. Built with PHP (Laravel) and MySQL, designed for easy deployment on Hostinger and API integration with any website.

## 🎯 Core Features

### Consumer-Facing Platform
- **Product Listing** - Browse products from multiple platforms (Amazon, Flipkart, Myntra, etc.)
- **Points-Based Cashback** - Earn points on every purchase (convertible to cash or gifts)
- **"Buy with Me" Feature** - Compare prices across platforms, find best deals
- **Referral Program** - Refer friends and earn points (non-e-commerce platforms only)
- **Points Redemption** - Convert points to cash or redeem for gifts
- **User Dashboard** - Track points, transactions, referrals, and earnings

### Admin Panel
- **Product Management** - Add, edit, and manage products
- **Affiliate Program Management** - Manage multiple affiliate networks
- **Points Management** - Monitor and manage user points
- **Cashback Settings** - Configure cashback and referral rates per program
- **Referral Management** - Track and manage referrals
- **Redemption Management** - Approve/reject withdrawal and gift redemption requests
- **Analytics Dashboard** - Comprehensive analytics and reporting

### Technical Features
- **Multi-network Support** - Amazon, Flipkart, Myntra, GPay, PhonePe, Upstox, Zerodha, Groww, PolicyBazaar, etc.
- **Points System** - Flexible points-based rewards (instead of direct cash)
- **Referral Tracking** - Cookie-based referral tracking system
- **Commission Split** - Automatic commission calculation and distribution
- **Click & Conversion Tracking** - Complete tracking and analytics
- **REST API** - Full API for integration
- **Sub-affiliate Support** - For non-e-commerce platforms only (policy compliant)

## 💰 Points-Based Cashback System

### How It Works

#### For E-commerce (Amazon, Flipkart, Myntra):
1. User browses products on the platform
2. Clicks "Buy with me" and selects a platform
3. Purchases through affiliate link
4. **Points credited automatically** (e.g., 20% cashback = 20 points per ₹100)
5. **NO referral commission** (Amazon policy compliant)

#### For Non-E-commerce (GPay, PhonePe, Upstox, etc.):
1. User A shares referral link with User B
2. User B clicks link and makes purchase
3. **User B gets cashback points** (e.g., 20% = 20 points)
4. **User A gets referral points** (e.g., 10% = 10 points)
5. **Full referral system active**

### Points Redemption
- **Cash Withdrawal** - Convert points to cash (minimum ₹100)
- **Gift Redemption** - Redeem points for gifts from catalog
- **Transaction History** - Complete history of all points transactions

## 🔗 Referral Program

> **Important:** Referral program is **enabled only for non-e-commerce platforms** (GPay, PhonePe, Upstox, Zerodha, Groww, PolicyBazaar, Impact, CJ, ShareASale, etc.) and **disabled for e-commerce** (Amazon, Flipkart, Myntra, etc.) to comply with affiliate policies.

### How Referrals Work
- **Referral Code Generation** - Each user gets unique referral codes
- **Cookie-Based Tracking** - Referral links tracked via cookies (30-day validity)
- **Automatic Points Credit** - Referral commission credited as points automatically
- **Referral Dashboard** - Track referrals, conversions, and earnings
- **Multi-Level Support** - Full referral system for supported platforms

---

## Supported Platforms

- **E-commerce (Direct Affiliate Only):** Amazon, Flipkart, Myntra
- **Finance/Referral/Apps (Sub-affiliate Enabled):** GPay, PhonePe, Upstox, Zerodha, Groww, PolicyBazaar, Impact, CJ, ShareASale, etc.

## Folder Structure
- `app/` – PHP application logic (Controllers, Services, Models)
- `public/` – Public web root (index.php, assets, .htaccess)
- `config/` – Configuration files (database, AI, API keys, etc.)
- `database/` – Migrations, seeds, and SQL scripts
- `resources/` – Blade views, language files (en, hinglish)
- `routes/` – Web and API route definitions
- `storage/` – Logs, cache, uploads
- `tests/` – PHPUnit tests
- `docs/` – Project documentation, API docs, architecture diagrams

## Deployment Notes
- Serve the `public/` directory as the web root; do not expose the repository root.
- Keep `vendor/`, `storage/`, `bootstrap/cache/`, migrations, and operational documentation available to the deployment process as required by the release runbook.
- Configure `.env` from the secret manager; never commit production credentials.
- Set writable permissions for `storage/` and `bootstrap/cache/` without making the entire application tree writable.
- Use PHP 8.2+ and a database version supported by Laravel 12. Complete the migration rehearsal, secret validation, partner certification, payout reconciliation, and rollback checks in `docs/RELEASE_OPERATIONS_RUNBOOK.md` before production release.

## Getting Started
1. Clone/download this repo
2. Run `composer install` (locally or via SSH)
3. Copy `.env.example` to `.env` and set your config
4. Run migrations: `php artisan migrate`
5. Set up your admin user and start building!

## Sub-affiliate Quick Start
1. Register a new sub-affiliate via the admin panel.
2. Generate a unique product link for the sub-affiliate.
3. Share the link; all clicks and sales are tracked automatically.
4. View sub-affiliate performance and manage payouts from the admin dashboard.
5. Add new affiliate programs (e.g., bank account, credit card, app download) from the admin panel as needed.
6. Parent-child affiliate relationships are automatically managed for supported (non-e-commerce) platforms.

For advanced setup and API details, see `PARTNER_INTEGRATION_CONTRACT.md` and `API_SECURITY_CONTRACT.md`. Deployment and staging procedures are in `docs/RELEASE_OPERATIONS_RUNBOOK.md`, with sign-off fields in `docs/STAGING_ACCEPTANCE_RECORD.md`.

---

## Current Documentation

| Document | Purpose |
|---|---|
| `API_SECURITY_CONTRACT.md` | HMAC authentication and mutation security contract |
| `PARTNER_INTEGRATION_CONTRACT.md` | Partner payloads, idempotency, retries, throttles, and reconciliation |
| `LARAVEL12_UPGRADE_REPORT.md` | Laravel 12 migration and zero-advisory verification |
| `docs/RELEASE_OPERATIONS_RUNBOOK.md` | Deployment, migration, rollback, secret rotation, and payout operations |
| `docs/STAGING_ACCEPTANCE_RECORD.md` | Staging evidence and release sign-off template |
| `docs/LICENSE_DECISION_RECORD.md` | Repository-owner license metadata decision |
| `FINAL_STAGING_HANDOFF_CHECKLIST.md` | Final staging gates, owners, evidence, and sign-off |
| `docs/CONTROL_EXECUTION_MATRIX.md` | Local versus staging-only control boundary |
| `docs/STAGING_OWNER_EXECUTION_GUIDE.md` | Step-by-step staging certification and evidence procedure |
| `STAGING_BLOCKER_REGISTER.md` | Owner, evidence, and stop-condition register for staging blockers |
| `STAGING_READINESS_REPORT.md` | Partner certification and payout reconciliation readiness |
| `IMPLEMENTATION_PROGRESS.md` | Cumulative remediation status and verification evidence |
| `FINAL_IMPLEMENTATION_SUMMARY.md` | Summary of the previous local-code remediation batch |

## API Reference
The implemented API contract and examples are documented in `PARTNER_INTEGRATION_CONTRACT.md` and `API_SECURITY_CONTRACT.md`. The live route source is `routes/api.php`.

## Admin Panel
Admin workflows are implemented in `app/Http/Controllers/AdminController.php`. Staging and production approval requirements are documented in `docs/STAGING_ACCEPTANCE_RECORD.md`.

## Contributing
Pull requests and suggestions are welcome! For major changes, please open an issue first to discuss what you would like to change.

## Support
For support, open an issue or contact [your-email@example.com].

## License
No license has been declared in the repository. Confirm the appropriate SPDX classification with the repository owner before external distribution.

## 📚 Documentation Table of Contents

### Core Documentation
- `API_SECURITY_CONTRACT.md` – Partner authentication and mutation security.
- `PARTNER_INTEGRATION_CONTRACT.md` – Partner API payload, retry, and reconciliation contract.
- `LARAVEL12_UPGRADE_REPORT.md` – Framework upgrade and dependency audit result.
- `docs/RELEASE_OPERATIONS_RUNBOOK.md` – Deployment and operations procedure.
- `docs/STAGING_ACCEPTANCE_RECORD.md` – Staging acceptance and release sign-off record.
- `docs/LICENSE_DECISION_RECORD.md` – Pending repository-owner license decision.
- `FINAL_STAGING_HANDOFF_CHECKLIST.md` – Final staging handoff checklist.
- `docs/CONTROL_EXECUTION_MATRIX.md` – Local versus staging-only execution boundary.
- `docs/STAGING_OWNER_EXECUTION_GUIDE.md` – Staging certification and evidence procedure.
- `STAGING_BLOCKER_REGISTER.md` – Owner-assigned staging blocker register.

### Current Status
- `STAGING_READINESS_REPORT.md` – Staging certification and payout reconciliation results.
- `IMPLEMENTATION_PROGRESS.md` – Cumulative implementation progress.
- `FINAL_IMPLEMENTATION_SUMMARY.md` – Previous local-code remediation summary.
- `NEXT_RELEASE_READINESS_REPORT.md` – Superseded pre-Laravel-12 readiness decision record.