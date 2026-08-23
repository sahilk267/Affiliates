# Phase 1 Remaining Decisions — ZenithSoles Affiliates

**Status:** Blocked — owner completion and approval required.

This checklist translates the current owner-provided direction into implementation decisions. It is not a substitute for the completed `docs/PILOT_DECISION_INPUT_TEMPLATE.md`, partner agreements, financial approval, privacy review, or staging evidence. An AI agent must not convert an **Unknown** item into a default value.

## Confirmed direction captured from the owner

| Decision area | Captured direction | Status boundary |
|---|---|---|
| Platform role | Users compare external merchant offers and complete purchases on the merchant platform through referral links. | Confirmed direction; checkout, merchant terms, and partner permissions remain external dependencies. |
| Core value | Centralized product search, offer comparison, ranking, and price-history visibility. | Confirmed direction; ranking and data-source rules remain incomplete. |
| Reward model | Consumer affiliate comparison with post-confirmation reward points rather than immediate cashback. | Draft selected model; formal owner sign-off is pending. |
| Distribution | WhatsApp, Telegram, creators, SEO, communities, Facebook, and Instagram. | Proposed channel set from owner; pilot channel priority and test thresholds remain unknown. |
| Product prioritization | Recent search demand, referral margin, and sale/margin combinations should influence product prioritization. | Direction captured; exact scoring and disclosure policy remain unknown. |
| Future scope | First-party products may be added later. | Future scope only; excluded from the current referral pilot. |

## Decisions required before Phase 1 can close

| ID | Decision required | Current status | Exact owner input needed | Stop condition |
|---|---|---|---|---|
| P1-01 | Initial category scope | Direction captured; bounded pilot still unknown | The owner proposed top-100 product discovery across platforms and Google; select the first bounded category set and define the source of “top 100.” | Broad “all products” scope is used without a bounded pilot decision. |
| P1-02 | Target customer segment | Owner direction captured; measurable pilot segment unknown | The owner stated “all online users”; define a measurable first-pilot segment and state out-of-scope audiences. | Product or channel work targets everyone without a primary segment. |
| P1-03 | Formal model approval | Draft selected | Approve the exact wording: “Consumer affiliate comparison with post-confirmation reward points,” with owner and date. | The model is treated as approved without sign-off. |
| P1-04 | Partner shortlist | Partially supplied; approval evidence unknown | List the initial 3–5 targets. Record Amazon and Flipkart as direct-API candidates only; record Meesho, Myntra, AJIO, Nykaa, Tata CLiQ, Snapdeal, and JioMart as prospects until current program access is evidenced. | A platform is called active without written approval or certification. |
| P1-05 | Data and permission basis | Partially researched; approval unknown | Select approved APIs, feeds, or partner-network sources for price, availability, search-demand, and history; record permission and retention rules. | Scraping, feeds, APIs, or platform data use is implemented without an approved basis. |
| P1-06 | Ranking policy | Direction captured; weights unknown | Define the deterministic ranking policy for price, availability, platform rating, price history, demand, and referral margin; disclose commercial influence. Shipping is currently out of scope because ZenithSoles is not the seller, but must not be silently treated as zero. | Commercially influenced ranking is presented as purely cheapest without disclosure. |
| P1-07 | Reward allocation basis | Owner direction captured; contract treatment pending | Use received affiliate commission as the owner-stated basis, then confirm whether this means gross network-reported commission or net settled commission after deductions, reversals, and disputes. | Code or financial calculations use the 4% example before the basis is approved. |
| P1-08 | Reward allocation categories | 40/40/20 direction captured; formal approval pending | Owner stated: 40% of received affiliate commission to customer points, 40% owner/business share, and 20% platform scaling/maintenance reserve. Confirm the accounting, disclosure, and reversal treatment. | Owner allocation or platform reserve is treated as a finalized financial policy without approval. |
| P1-09 | Confirmation and points usability | Approximate delay captured; partner terms unknown | The owner described roughly two months because partner payment may arrive after 2–3 months; confirm partner-specific approval/reversal windows and when points become usable. | One universal confirmation period is assumed for all partners. |
| P1-10 | Gifts | Direction captured; policy unknown | Define which users qualify, selection method, budget/cap, frequency, public disclosure, return/fraud handling, and accounting treatment. | Gifts are promised or distributed without an approved budget and eligibility rule. |
| P1-11 | Pilot metrics | Unknown | Provide numeric thresholds for clicks, orders, repeat users, referral revenue, reward/gift cost, contribution margin, disputes, and settlement time; “ab samajh jao” is not a numeric approval. | Go/no-go claims are made without pre-approved thresholds. |
| P1-12 | Owners and approvals | Unknown | Name Product, Release, Security/Privacy, and Finance/Payout owners; add approval dates and evidence links. | A phase is marked ready without named accountable owners. |
| P1-13 | Pilot window and environment | Unknown | Provide planned pilot dates and an approved staging URL. Do not provide production credentials for certification. | Staging or production is used without an approved target and test window. |

## Current gate

The partner research record is `audit/phase1-partner-research-2026-08-24.md`. The machine-readable gate is `audit/phase1-gate.json`. The current validator should remain blocked until the required fields and all four owner sign-offs are completed:

```bash
python3 tools/validate_pilot_decision_inputs.py
python3 tools/validate_pilot_decision_inputs.py --require-approved
```

The report-only command may be used for progress tracking. The strict command must return non-zero while any required decision or approval is missing. Production approval remains **not approved** regardless of Phase 1 status.

## Owner response format

The owner may complete `docs/PILOT_DECISION_INPUT_TEMPLATE.md` directly. For the two ambiguous reward questions, the minimum response must explicitly state:

> The percentages are calculated on **[gross order value / eligible order value / received affiliate commission]**. The 4% example allocates **[exact customer points share]**, **[exact owner category/share]**, and **[exact platform operating or reserve share]**. The confirmation period is **[fixed / partner-specific]**, and points become usable under **[approved condition]**.

Until this statement and the remaining approvals are recorded, no reward ledger, ranking algorithm, gift automation, or partner activation should be built from the example alone.
