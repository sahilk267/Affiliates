# ZenithSoles Affiliates Partner Integration Contract

This document defines the production contract for partner networks and merchant integrations that send affiliate click and conversion events to ZenithSoles Affiliates. The contract is intentionally aligned with the implementation in `routes/api.php`, `VerifyPartnerSignature`, `ApiController`, `AffiliateTrackingService`, `PointsService`, and `PayoutService`.

## 1. Integration principles

Partners must treat the API as an **at-least-once delivery interface**. Requests may be retried after network failures, but retries must reuse the same idempotency identity. The platform performs atomic persistence for attribution and financial mutations, and it fails closed when a reward or ledger transaction cannot be created.

The partner must preserve the original `click_id` returned by the click endpoint through the full customer journey. A conversion without a valid click identifier is rejected before financial processing. A click can be converted only once; subsequent attempts for the same click return a conflict unless the request is an idempotent replay of the original `partner_event_id`.

## 2. Endpoint summary

| Endpoint | Purpose | Authentication | Throttle | Idempotency |
|---|---|---|---:|---|
| `POST /api/affiliate/click` | Create an attributed click and receive an affiliate URL | Public request controls; no partner signature | 60 requests/minute per partner key or IP | Not required |
| `POST /api/affiliate/conversion` | Report a partner conversion and create the commission/reward pipeline | HMAC partner signature | 30 requests/minute per partner key or IP | `partner_event_id`; `Idempotency-Key` is accepted as fallback |
| `POST /api/points/credit` | Credit points from an authenticated partner event | HMAC partner signature | 10 requests/minute per partner key or IP | Required `Idempotency-Key` |
| `POST /api/referral/track` | Record a referral-code visit | Public request controls | 30 requests/minute per IP | Not required |
| `GET /api/affiliate/link/{shortCode}` | Read link metadata | Public read endpoint | API group limit | Not applicable |

The platform's read endpoints for user statistics, points balances, and referral information require the authenticated owner or an administrator. They must not be used as a substitute for partner mutation authentication.

## 3. HMAC authentication

Authenticated partner mutations must include the following headers.

| Header | Required value |
|---|---|
| `X-Affiliate-Key` | The provisioned partner key matching `AFFILIATE_API_KEY` |
| `X-Affiliate-Timestamp` | Unix timestamp within five minutes of the platform clock |
| `X-Affiliate-Signature` | Lowercase hexadecimal HMAC-SHA256 digest |
| `Idempotency-Key` | Required for points credit; recommended on every mutation |
| `Content-Type` | `application/json` |

The signature is calculated over the exact bytes sent in the HTTP request body:

```text
payload = exact_raw_json_bytes
message = unix_timestamp + "." + payload
signature = HMAC-SHA256(message, AFFILIATE_API_SECRET)
```

The partner must not parse and reserialize the body between signing and transmission. Whitespace, property ordering, escaping, and numeric representation must remain unchanged. Timestamp failures, key failures, and signature failures are authentication errors and must not be retried without correcting the request.

## 4. Click attribution contract

A partner or storefront sends a valid `link_id` to `POST /api/affiliate/click`. Optional fields include `ip_address`, `user_agent`, `referrer`, and `referral_code`. The platform records the click in one transaction, increments the link click counter, and returns the canonical click identifier.

```json
{
  "link_id": 42,
  "referrer": "https://partner.example/item",
  "referral_code": "REF-ABC123"
}
```

A successful response has the following shape:

```json
{
  "status": "success",
  "data": {
    "click_id": 901,
    "affiliate_url": "https://merchant.example/item?click_id=901",
    "redirect_url": "https://merchant.example/item?click_id=901"
  }
}
```

Partners must persist `click_id` as an opaque integer and send it back unchanged with the conversion. GeoIP enrichment is intentionally optional; missing provider configuration must not cause fabricated country or city values to be stored.

## 5. Conversion reporting contract

`POST /api/affiliate/conversion` requires `click_id`, `partner_event_id`, and `event_type`. Supported event types are `purchase`, `signup`, `download`, `install`, `lead`, `click`, and `other`. Monetary values must be non-negative, quantities must be positive integers, and currency must be a three-character code when supplied.

```json
{
  "click_id": 901,
  "partner_event_id": "merchant-order-2026-000123",
  "event_type": "purchase",
  "conversion_value": 1999.00,
  "currency": "INR",
  "order_id": "2026-000123",
  "customer_id": "customer-77",
  "product_id": "sku-42",
  "product_name": "Example Product",
  "quantity": 1,
  "event_data": {
    "source": "merchant-webhook"
  }
}
```

The transaction creates the conversion and affiliate commission, marks the click converted, updates link counters, and invokes cashback and referral reward processing. The conversion is marked processed only after the downstream reward operations complete successfully. If a reward operation fails, the request returns an error and the transaction is rolled back rather than silently recording a partial financial outcome.

A repeated `partner_event_id` returns the existing conversion with `idempotent: true`. It does not create a second conversion, commission, cashback credit, or referral credit. A different partner event targeting an already converted click returns a conflict and requires partner-side investigation.

## 6. Points-credit contract

`POST /api/points/credit` is reserved for authenticated partner-originated credits that are outside the conversion pipeline or are explicitly represented as a points ledger reference.

```json
{
  "user_id": 77,
  "points": 100,
  "description": "Promotional bonus",
  "reference_type": "bonus",
  "reference_id": 123
}
```

The request must include a unique `Idempotency-Key`. The platform locks the user's wallet row, checks the existing idempotency record, applies the balance change, and writes the completed ledger entry in one transaction. Reusing a key returns the original transaction and does not change the balance a second time. Partners must never reuse a key for a semantically different operation.

## 7. Response and retry rules

| Condition | HTTP status | Partner action |
|---|---:|---|
| Valid mutation | `200` | Persist the returned identifiers and ledger result |
| Validation failure | `422` | Correct the payload; do not blindly retry |
| Invalid or expired HMAC credentials | `401` | Correct credentials, timestamp, or signature |
| Missing platform configuration | `503` | Retry only after the platform owner confirms recovery |
| Duplicate conversion event | `200` with `idempotent: true` | Treat as successful replay; do not create another event |
| Already-converted click | `409` | Investigate event identity and attribution before retrying |
| Rate limit exceeded | `429` | Honor `Retry-After` when supplied and use exponential backoff |
| Unexpected processing failure | `500` | Retry with the same idempotency identity after backoff |

Retries should use bounded exponential backoff with jitter. A partner must log the request identifier, partner event identifier, click identifier, response status, and final outcome for operational reconciliation.

## 8. Financial lifecycle and reconciliation

Affiliate commissions begin in `pending` status. Administrators may approve or cancel a pending commission. A commission may be marked paid only after approval, and each payout records the actor, method, transaction reference, and payout timestamp. These state transitions are guarded by the domain model and orchestrated through `PayoutService` under a database transaction.

Cash withdrawals debit the user's wallet and create a pending redemption atomically. Repeated withdrawal submissions with the same idempotency key return the original redemption. Rejected redemptions refund the original debit exactly once through an idempotent refund transaction. Completion is allowed only after approval and records the payout reference and actor.

Partners should reconcile at least the following identifiers: `partner_event_id`, `conversion_id`, `click_id`, `commission_id`, `points_transaction_id`, `redemption_id`, and any external payout or order reference. The platform's structured logs include these identifiers on conversion, reward, and ledger events.

## 9. Required partner operational controls

Before production enablement, each partner must implement secret storage outside source control, HMAC signing over raw request bytes, durable idempotency-key storage, retry backoff, dead-letter handling for persistent failures, and daily reconciliation against the platform's conversion and payout exports. Partner secrets must be rotated through the deployment secret manager and must never be placed in repository files.

The repository does not implement merchant-specific adapters for Amazon, Flipkart, or other networks because those integrations require real credentials, production webhook specifications, and commercial account configuration. It also does not enable a concrete GeoIP provider without a provider contract and privacy review. Load testing, production secret-manager wiring, and composer security-advisory remediation remain deployment-gate activities rather than local-code assumptions.

## 10. Local verification

The following commands verify the repository implementation without requiring live partner credentials:

```bash
composer install
APP_ENV=local DB_CONNECTION=sqlite DB_DATABASE=database/database.sqlite php artisan migrate:fresh --force
find app database routes config bootstrap tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
./vendor/bin/phpunit --configuration phpunit.xml.dist --testdox
```

The security-specific details in this document complement `API_SECURITY_CONTRACT.md`; if the two documents appear inconsistent, the deployed route and middleware configuration must be treated as authoritative and the documentation must be corrected before partner onboarding.

## References

1. [`API_SECURITY_CONTRACT.md`](API_SECURITY_CONTRACT.md), repository partner authentication contract.
2. [`routes/api.php`](routes/api.php), repository API route and middleware definitions.
3. [`app/Http/Middleware/VerifyPartnerSignature.php`](app/Http/Middleware/VerifyPartnerSignature.php), repository HMAC verification implementation.
4. [`app/Services/PayoutService.php`](app/Services/PayoutService.php), repository atomic payout and redemption orchestration.
5. [`app/Services/PointsService.php`](app/Services/PointsService.php), repository wallet ledger and idempotency implementation.
6. [`app/Services/AffiliateTrackingService.php`](app/Services/AffiliateTrackingService.php), repository shared click attribution implementation.
7. [`app/Http/Controllers/ApiController.php`](app/Http/Controllers/ApiController.php), repository conversion and points API implementation.
