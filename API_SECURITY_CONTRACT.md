# Affiliate API Security Contract

## Protected mutation endpoints

`POST /api/affiliate/conversion` and `POST /api/points/credit` require the `partner.signature` middleware. Read-only catalog, click, balance, and referral endpoints remain separately scoped and should not be treated as trusted mutation APIs.

## Required headers

| Header | Requirement |
|---|---|
| `X-Affiliate-Key` | Must match `AFFILIATE_API_KEY`. |
| `X-Affiliate-Timestamp` | Unix timestamp within five minutes of server time. |
| `X-Affiliate-Signature` | HMAC-SHA256 over `timestamp + '.' + raw_request_body` using `AFFILIATE_API_SECRET`. |
| `Idempotency-Key` | Required for points credit and accepted as the conversion partner event fallback. |

The partner must send the exact JSON bytes used to compute the signature. A request example is:

```text
payload = JSON.stringify(body)
message = unix_timestamp + "." + payload
signature = HMAC_SHA256(message, AFFILIATE_API_SECRET)
```

The conversion body must include `click_id`, `partner_event_id`, and an allowed `event_type`. Monetary values are validated as non-negative numeric values, quantities as positive integers, and currency as a three-character code. A repeated `partner_event_id` returns the original conversion result without creating another conversion, commission, or reward.

The points-credit body must include `user_id`, positive bounded `points`, `description`, and a unique idempotency key. Repeating the same key returns the original points transaction and does not increment the user balance again.

## Required environment configuration

Copy `.env.example` to `.env`, generate `APP_KEY`, and set non-empty `AFFILIATE_API_KEY` and `AFFILIATE_API_SECRET` values in every non-local environment. `ADMIN_PASSWORD` is required when running the admin seeder outside local/testing environments. No credential should be committed to the repository.

## Verification commands

```bash
composer install
php artisan migrate:fresh --force
./vendor/bin/phpunit --configuration phpunit.xml.dist
```

CI must run the same checks without suppressing failures. Partner credentials should be rotated through the deployment secret manager, not through source control.
