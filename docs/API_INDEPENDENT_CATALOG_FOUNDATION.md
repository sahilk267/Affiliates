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

## Local evidence

The feature suite covers:

- source-tagged snapshot persistence;
- preservation of unknown price/rating values;
- source-filtered latest/history ordering;
- rejection of invalid ratings;
- explicit ranking weights and stable ordering.

These tests use local fixtures only. They do not establish Amazon, Flipkart, network, merchant, price-history, or production capability.
