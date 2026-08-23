# Phase 1 Partner and Product-Data Research — 2026-08-24

**Status:** Research complete; partner activation and production integration remain blocked pending owner approvals, partner acceptance, credentials, and contract evidence.

## Executive conclusion

The platform should not implement one assumed universal affiliate API. The verified integration surface is mixed:

1. **Amazon:** the official documentation states that PA-API 5 is deprecated and is being replaced by the Creators API. New work should target Creators API onboarding and country/locale-specific eligibility, not the deprecated PA-API endpoint. [1]
2. **Flipkart:** the official affiliate documentation explicitly supports registered affiliates and documents Product APIs, Offer APIs, and Report APIs over HTTPS with JSON/XML responses. It specifically lists shopping-comparison sites and mobile applications as use cases. [2]
3. **Meesho, Nykaa, and Snapdeal:** the official pages reviewed expose creator/influencer publisher programs and personalized-link workflows. They do not, in the reviewed public pages, establish a public product-catalog API, price-history API, or bulk comparison feed. [3] [4] [5]
4. **Tata CLiQ:** an official affiliate URL exists, but the extracted page did not provide enough technical detail to verify a public product API or feed. This must remain **unverified** until the partner supplies current publisher documentation or access. [6]
5. **Myntra and AJIO:** the current reviewed primary sources do not establish direct public API access. A publisher network may list campaigns, but campaign listing is not proof of access, approval, product-feed rights, or price-data rights.
6. **JioMart:** the reviewed affiliate-service-provider URL returned a “page not found” result. No direct API or affiliate contract should be assumed from that URL. [7]
7. **Intermediary networks:** Cuelinks publicly claims a developer API with campaign discovery, URL-to-tracked-link conversion, transaction/report endpoints, and named coverage including Myntra and AJIO. Admitad publicly describes deep links, postback URLs, XML product feeds, five SubID parameters, and publisher access for cashback/loyalty platforms. These are provider claims and require account approval, campaign approval, terms review, credentials, and live test evidence before being treated as project capability. [8] [9]

## Verified source findings

| Platform or route | Evidence observed | What ZenithSoles may build after approval | What remains unproven |
|---|---|---|---|
| Amazon Creators API | Official Amazon documentation says PA-API 5 is deprecated and the Creators API is the supported successor for product-catalog access. [1] | A dedicated Amazon connector for approved catalog fields, localized prices/offers where permitted, affiliate link generation, and reporting. | Account eligibility, India locale access, exact fields/limits, attribution window, rate limits, and terms for comparison/price-history storage. |
| Flipkart Affiliate API | Official docs describe registered-affiliate Product, Offer, and Report APIs; Product APIs support search and Product ID queries; Offer APIs expose active/deal offers; Report APIs expose affiliate-driven orders. [2] | A direct Flipkart connector for product/offer ingestion, deep-link generation according to its contract, and conversion/report reconciliation. | Current account approval, credentials, exact endpoint schema/version, rate limits, commission terms, and permission to persist history. |
| Meesho creator program | Official page is a creator program for promoting Meesho products on social media and earning through creator activity. [3] | A link-based campaign connector only if Meesho grants machine-readable access or an approved network supplies a permitted feed/link API. | Public product API, bulk catalog feed, price-history rights, comparison rights, and program terms for a technology platform. |
| Nykaa NAP | Official NAP page describes creator signup, organic recommendations, earnings dashboard, campaigns, and creator tools; official help center lists affiliate-program questions including eligibility and payment. [4] | A link-based Nykaa connector after acceptance, or a network connector if an approved feed/API is available. | Public product API/feed, bulk price history, comparison rights, approval of a non-creator comparison platform, and exact settlement/reversal terms. |
| Snapdeal influencer program | Official page describes free signup, personalized links, influencer dashboard, product-link generation, and payment for successful sales. [5] | Link generation and sub-ID tracking if the platform is accepted; product catalog only through an approved feed/API. | Public catalog API, bulk price/availability feed, comparison rights, and platform-level partner contract. |
| Tata CLiQ affiliate page | Official affiliate URL is present, but the extracted content did not expose technical API/feed details. [6] | Contact/partner onboarding first; build only the interface documented by Tata CLiQ or an approved network. | Current program terms, API/feed, deep-link method, attribution, reversals, and price-history permission. |
| Myntra/AJIO via intermediary | Cuelinks claims coverage for Myntra and AJIO and an API that can discover campaigns and convert URLs into tracked links. [8] | Use an intermediary adapter if the campaigns are approved and the provider contract permits product discovery, link conversion, reporting, and data retention. | Campaign availability for this publisher, approval status, product-feed fields, price freshness, attribution/reversal terms, and rights to rank/display merchant data. |
| JioMart | Reviewed official affiliate-service-provider URL returned “We couldn't find the page.” [7] | Treat as a prospect; request current official partner documentation or verify an approved network campaign. | Any current official affiliate API, feed, deep-link contract, and comparison/data rights. |
| Admitad route | Official publisher page describes deep links, postback URLs, XML feeds, SubID parameters, and publishers such as cashback/loyalty platforms. [9] | Evaluate as a multi-merchant adapter for approved campaigns, feed ingestion, SubID tracking, postbacks, and reconciliation. | Specific named-campaign availability, account approval, exact fields, country restrictions, fees, payout/reversal policy, and rights to store/compare product data. |

## Recommended integration architecture

### 1. Source adapters, not one shared assumption

Use a connector interface with separate adapters for `amazon_creators`, `flipkart_affiliate`, and each approved intermediary network. Each adapter should expose only fields that the source contract actually supplies:

```text
searchProducts(query, category, page)
getProduct(externalProductId)
getOffers(externalProductId)
createTrackedLink(destinationUrl, subId)
fetchTransactions(cursor)
fetchReports(period)
```

The interface must allow an adapter to return **unsupported** for any operation. For example, a link-only program must not be represented as if it supports catalog search, stock, price, or historical snapshots.

### 2. Separate commercial attribution from product data

A tracked affiliate link proves a referral path, not a reliable product catalog. The platform should maintain separate records for:

| Record | Purpose |
|---|---|
| Product identity | Canonical product and variant matching across merchants |
| Merchant offer | Merchant URL, current price, availability, seller/platform rating, currency, and captured time |
| Affiliate link | Tracking URL, source adapter, campaign, SubID dimensions, and link-created time |
| Price snapshot | Source, observed value, observed time, currency, and permission/retention classification |
| Transaction/reward event | Partner event, approval/confirmation state, received commission, reward allocation, reversal, and reconciliation references |

A product should not be ranked or shown as “cheapest” unless the displayed offers share a comparable product identity, currency, variant, and timestamp. Missing or stale values must be labeled rather than filled by inference.

### 3. Use a bounded ranking service, not an ungoverned autonomous agent

The owner described an intelligent agent for research and ranking. The safe implementation is a deterministic ranking service whose inputs and weights are owner-approved. The agent may collect candidate products or explain results, but it must not silently change weights or place high-margin offers above cheaper offers without a visible commercial-ranking disclosure.

Minimum ranking inputs should be explicitly configured only after approval:

- price and price timestamp;
- availability;
- discount/offer fields supplied by the source;
- platform/seller rating where the source defines it;
- price-history signals;
- referral economics as a disclosed secondary factor, never as an undisclosed “cheapest” claim.

Shipping need not be part of the current comparison if the platform is not selling or fulfilling the product. It should be marked **not included** rather than silently assumed to be zero. Taxes, coupons, membership pricing, seller differences, and delivery charges may still make two displayed prices non-comparable and require explicit labeling.

### 4. Price history and “top 100” require approved sources

The phrase “top 100 products on every platform including Google” is a product objective, not yet a verified data source. Google search popularity, merchant search demand, and platform bestseller lists are different datasets. Before implementation, the owner must select permitted sources and definitions for:

- what “top 100” means;
- the reference period, currently described as the latest two months;
- whether rankings are global, India-wide, language-specific, or category-specific;
- refresh frequency;
- deduplication across variants and sellers;
- storage duration for historical prices;
- API/feed/terms permission.

No scraping or automated extraction should be implemented for a platform unless its terms or written permission allow it. A public webpage is not automatically a license for bulk copying, price-history storage, or affiliate-use comparison.

## How the referral and reward flow should work

The current owner direction is compatible with a **post-confirmation reward-points ledger**, not an immediate cashback promise:

1. User searches and views an offer.
2. Platform records a click and sends the user through an approved tracked link.
3. Merchant/network reports a conversion or transaction event.
4. The platform keeps the reward in a pending state; it does not promise that the amount is immediately usable.
5. After the partner confirms and the platform has received or can reconcile the commission, the system records the received commission amount.
6. The approved allocation is then applied to the received commission: 40% customer reward points, 40% owner/business share, and 20% platform scaling/maintenance reserve.
7. Points become usable only under the approved confirmation and reversal policy.
8. Returns, cancellations, chargebacks, network reversals, and reporting corrections must create an auditable compensating ledger entry rather than deleting history.

The exact partner settlement period, approval state, reversal rules, and whether the 40/40/20 split is calculated before or after network deductions remain contract-dependent. No universal two-month rule should be coded until partner terms are collected.

## Partner onboarding sequence

| Step | Required evidence | Outcome |
|---:|---|---|
| 1 | Owner selects the first 3–5 partner targets | Bounded pilot shortlist |
| 2 | Each target is marked prospect, approved staging, or approved pilot | No unverified “active” status |
| 3 | Partner/network accepts the publisher/platform | Account and campaign access |
| 4 | Technical documentation and credentials are supplied securely | Adapter contract, no secrets in Git |
| 5 | Product/feed/link fields are mapped with sanitized examples | Field mapping and schema tests |
| 6 | Attribution, SubID, postback/reporting, approval, reversal, and payout terms are recorded | Financial and tracking contract |
| 7 | Negative staging checks run first | Invalid signature, expired event, malformed payload, wrong key |
| 8 | One disposable valid staging event is run only with explicit authorization | Conversion, report, and reward reconciliation |
| 9 | Replay/conflict/rate-limit checks pass | Idempotency and abuse controls |
| 10 | Partner and security owners sign evidence | Campaign eligible for pilot |

## Current recommendation

For the first implementation slice, prioritize **Flipkart direct integration** because the official API documentation explicitly supports comparison sites and exposes product, offer, and report APIs. Treat **Amazon as a separate Creators API migration/onboarding track**, not a PA-API implementation. For the remaining named merchants, start with a **network adapter evaluation** only after the owner chooses the intermediary, accepts its commercial/data terms, and confirms named campaigns. Do not implement scraping or a multi-platform catalog importer from public pages.

## Remaining owner decisions

The research does not close these items:

- first pilot category or bounded category set;
- precise definition and source for “top 100” and the two-month demand window;
- initial 3–5 partners and confirmed approval status;
- direct versus intermediary route for each partner;
- permitted product/price/availability/history fields and retention period;
- ranking weights and commercial-ranking disclosure;
- exact points currency and voucher conversion, including whether 10 points = ₹1 is approved;
- network deduction/reversal treatment before the 40/40/20 allocation;
- gift budget and eligibility;
- named owners, pilot dates, staging URL, and metric thresholds.

## References

[1]: https://webservices.amazon.com/paapi5/documentation/ "Amazon Product Advertising API — PA-API 5 Deprecation Notice and Creators API"
[2]: https://affiliate.flipkart.com/api-docs/af_overview.html "Flipkart Affiliate APIs Overview"
[3]: https://affiliate.meesho.com/ "Meesho Affiliate Web / Creator Program"
[4]: https://affiliate.nykaa.com/ "Nykaa Affiliate Program"; https://www.nykaa.com/help-center/topic/21 "Nykaa Help Center — Affiliate Program"
[5]: https://partners.snapdeal.com/ "Snapdeal Influencer Program"
[6]: https://www.tatacliq.com/affiliates "Tata CLiQ Affiliates"
[7]: https://www.jiomart.com/s/affiliate-service-provider-in/69670 "JioMart affiliate-service-provider URL reviewed; page not found at retrieval time"
[8]: https://developers.cuelinks.com/ "Cuelinks V3 Monetization API"
[9]: https://www.admitad.com/affiliates/ "Admitad Affiliate Publishers"
