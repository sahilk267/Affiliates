# API-Independent Catalog Foundation — ZenithSoles Affiliates

**Status:** Implemented foundation; external source adapters, partner permissions, and commercial ranking weights remain pending.

## Purpose

This foundation lets ZenithSoles build and test product-comparison behavior before merchant APIs or affiliate-network access are available. It deliberately stores source observations separately from affiliate links and does not claim that any fixture is a real merchant observation.

## Snapshot contract

`product_price_snapshots` stores one timestamped observation for a `product_links` offer. The source is mandatory; price, currency, availability, rating, rating count, original price, discount, external offer ID, and metadata may be unknown and remain `NULL`.

| Field | Meaning | Unknown handling |
|---|---|---|
| `product_link_id` | Existing product/merchant-link record | Required local relation |
| `source` | Adapter or approved feed identifier | Required; never inferred |
| `external_offer_id` | Source-side offer identity | Nullable until supplied |
| `observed_at` | Source observation time | Required; recorded from source or explicit fixture time |
| `price` | Observed offer price | Nullable; no zero default |
| `currency` | Observed currency | Nullable; no currency conversion is performed |
| `availability` | Source-reported availability | Nullable; never defaulted to “in stock” |
| `rating` / `rating_count` | Source-reported rating fields | Nullable and rating is validated to 0–5 |
| `original_price` / `discount_percent` | Source-reported comparison fields | Nullable; discount validated to 0–100 |
| `metadata` | Sanitized source-specific fields | Nullable array; secrets must not be stored |

The `ProductPriceSnapshotService` records observations transactionally, validates numeric ranges, supports source-filtered latest/history queries, and preserves unknown values. It does not call a remote API and it does not infer a missing price, stock state, rating, discount, or timestamp.

## Ranking contract

`ProductRankingService` is intentionally source-agnostic. The caller must supply already-normalized feature values in the range 0–1 and explicit weights that sum to 100:

- `price`;
- `availability`;
- `rating`;
- `price_history`;
- `demand`;
- `referral_margin`.

The service does not decide how raw prices, demand, or history are normalized. Lower price should be converted to a higher `price` feature by the approved caller policy; no normalization default is embedded. Equal scores use a stable offer ID tie-breaker.

Referral margin is represented as an explicit feature so the product owner can approve and disclose its use. It must not be silently used to claim that an offer is the cheapest. Shipping is not a zero value in this foundation; it is simply not a feature until the owner approves whether and how it should be displayed for external merchant offers.

## Adapter boundary

Future adapters should convert external source responses into the snapshot and ranking contracts:

```text
source response
  -> sanitized field mapping
  -> ProductPriceSnapshotService::record()
  -> approved normalization and weights
  -> ProductRankingService::rank()
  -> user-facing comparison with source timestamp and disclosure
```

No adapter should be marked active because a public affiliate page exists. Each adapter requires current source documentation, partner/network approval, secure credentials, field mapping, terms/data-permission review, and approved staging certification.

## Adapter interface and safety switches

Future provider implementations should implement `App\\Services\\Contracts\\ProductSourceAdapter`. The interface exposes a stable source name and a `normalizeOffer()` method that converts provider payloads into `ProductPriceSnapshotService` input. It does not grant provider access, approve terms, or hide missing fields. Credentials and provider-specific behavior remain outside the interface.

`config/comparison.php` defines the preview boundary. Comparison preview is enabled by default for local development, while rewards, vouchers, and gifts are disabled by default. The controller gate returns not-found for public comparison and outbound-click routes when preview is disabled. These comparison-preview switches do not disable legacy signed financial endpoints and do not replace partner approval, Finance/Payout approval, privacy review, or staging certification.

## Read-only comparison preview

The public `/products` and `/products/{id}` routes use the snapshot-backed comparison path. Products are ordered by lowest known observed price with stable product or offer ID ties; products without a known price appear after known prices. The preview does not apply commission-first ordering, does not infer availability, and does not promise that an observation is current.

Use the synthetic preview data only in a local or testing environment:

```bash
php artisan migrate:fresh --seed --seeder="Database\\Seeders\\ComparisonPreviewSeeder"
```

`ComparisonPreviewSeeder` is not called by the default `DatabaseSeeder` and refuses to run outside `local` or `testing`. Its merchant names, URLs, products, prices, and ratings are synthetic `example.test` fixtures and must never be represented as live partner data.

The existing `/buy/{productId}/{programId}` route remains an external redirect boundary and is subject to the same preview gate. When enabled, it records a local click for a valid tracked link and redirects externally, but it does not activate commissions, points, vouchers, gifts, or settlement behavior.

## Local evidence

The feature suite covers:

- source-tagged snapshot persistence;
- preservation of unknown price/rating values;
- source-filtered latest/history ordering;
- rejection of invalid ratings;
- explicit ranking weights and stable ordering;
- public disclosure boundaries and disabled-preview gate behavior.

These tests use local fixtures only. They do not establish Amazon, Flipkart, network, merchant, price-history, or production capability.
