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

## Hostinger Deployment Notes
- Upload all files except `/storage`, `/tests`, `/docs` to your Hostinger web root
- Set `public/` as your web root in Hostinger panel
- Configure `.env` for database, AI/LLM API keys, and other secrets
- Set file/folder permissions for `storage/` and `bootstrap/cache/`
- Use PHP 8.1+ and MySQL 5.7+/8.0

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

For advanced setup and API details, see `/docs/sub-affiliate.md` (or the relevant documentation in `/docs`).

---

For full architecture, DB schema, and API docs, see `/docs`.

## API Reference
For full API documentation and example requests, see `/docs/api.md`.

## Admin Panel
See `/docs/admin-panel.md` for admin features, screenshots, and usage tips.

## Contributing
Pull requests and suggestions are welcome! For major changes, please open an issue first to discuss what you would like to change.

## Support
For support, open an issue or contact [your-email@example.com].

## License
[Specify your license here, e.g., MIT, Proprietary, etc.]

## 📚 Documentation Table of Contents

### Core Documentation
- `/docs/COMPLETE_IMPLEMENTATION_PLAN.md` – Complete implementation plan
- `/docs/DATABASE_SCHEMA_V2.md` – Database schema v2.0 (points system)
- `/docs/FEATURE_SPECIFICATIONS.md` – Detailed feature specifications
- `/docs/IMPLEMENTATION_ROADMAP_V2.md` – Step-by-step implementation roadmap

### Existing Documentation
- `/docs/api.md` – API documentation
- `/docs/admin-panel.md` – Admin panel guide
- `/docs/architecture.md` – System architecture
- `/docs/db-schema.md` – Original database schema

### Planning Documents
- `/PRIORITIZATION_ROADMAP.md` – Feature prioritization
- `/PROJECT_STATUS_REPORT.md` – Current project status
- `/IMPLEMENTATION_COMPLETE.md` – Previous implementation status 