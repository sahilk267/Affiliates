# ZenithSoles Affiliate Management System

A scalable, AI/LLM-optimized, multi-language (English + Hinglish), load-balanced affiliate management platform for banners, links, and product data. Built with PHP (Laravel-ready structure) and MySQL, designed for easy deployment on Hostinger and API integration with any website.

## Features
- Admin panel for managing banners, links, products, and affiliate networks
- Multi-network support (Amazon, Myntra, Flipkart etc.)
- Product auto-sync and enrichment via AI/LLM (Gemini, OpenAI, Claude, etc.)
- Smart token usage: deduplication, caching, incremental AI calls
- English + Hinglish data storage and translation
- REST API for multi-site integration
- Click tracking and analytics
- Load-balanced and scalable

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

---

For full architecture, DB schema, and API docs, see `/docs`. 