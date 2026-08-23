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
| P1-01 | Initial category scope | Unknown | Select one bounded initial category or a small approved category set for the pilot. | Broad “all products” scope is used without a bounded pilot decision. |
| P1-02 | Target customer segment | Partially described | Convert “online shoppers” into a measurable primary segment and state out-of-scope audiences. | Product or channel work targets everyone without a primary segment. |
| P1-03 | Formal model approval | Draft selected | Approve the exact wording: “Consumer affiliate comparison with post-confirmation reward points,” with owner and date. | The model is treated as approved without sign-off. |
| P1-04 | Partner shortlist | Unknown | List initial 3–5 networks/merchants and mark each prospect, approved staging, or approved pilot with source. | A platform is called active without written approval or certification. |
| P1-05 | Data and permission basis | Unknown | Identify approved price, availability, search-demand, and price-history sources plus permitted refresh/use rules. | Scraping, feeds, APIs, or platform data use is implemented without an approved basis. |
| P1-06 | Ranking policy | Unknown | Define how price, shipping, discount, stock, seller quality, price history, and referral economics are weighted and disclosed. | Commercially influenced ranking is presented as purely cheapest without disclosure. |
| P1-07 | Reward allocation basis | Ambiguous | Confirm whether the stated percentages apply to gross order value, eligible order value, or received affiliate commission. | Code or financial calculations use the 4% example before the basis is approved. |
| P1-08 | Reward allocation categories | Ambiguous | Confirm the meaning and approval of customer points, owner allocation, and platform scaling/maintenance allocation. | Owner allocation or platform reserve is treated as a finalized financial policy without approval. |
| P1-09 | Confirmation and points usability | Partially described | Confirm whether the approximately two-month delay is fixed or partner-specific, and define pending, confirmed, reversed, and usable states. | One universal confirmation period is assumed for all partners. |
| P1-10 | Gifts | Unknown | Define eligibility, selection method, budget/cap, frequency, disclosure, returns, fraud handling, and accounting treatment. | Gifts are promised or distributed without an approved budget and eligibility rule. |
| P1-11 | Pilot metrics | Unknown | Provide numeric thresholds for clicks, orders, repeat users, referral revenue, reward/gift cost, contribution margin, disputes, and settlement time. | Go/no-go claims are made without pre-approved thresholds. |
| P1-12 | Owners and approvals | Unknown | Name Product, Release, Security/Privacy, and Finance/Payout owners; add approval dates and evidence links. | A phase is marked ready without named accountable owners. |
| P1-13 | Pilot window and environment | Unknown | Provide planned pilot dates and an approved staging URL. Do not provide production credentials for certification. | Staging or production is used without an approved target and test window. |

## Current gate

The machine-readable gate is `audit/phase1-gate.json`. The current validator should remain blocked until the required fields and all four owner sign-offs are completed:

```bash
python3 tools/validate_pilot_decision_inputs.py
python3 tools/validate_pilot_decision_inputs.py --require-approved
```

The report-only command may be used for progress tracking. The strict command must return non-zero while any required decision or approval is missing. Production approval remains **not approved** regardless of Phase 1 status.

## Owner response format

The owner may complete `docs/PILOT_DECISION_INPUT_TEMPLATE.md` directly. For the two ambiguous reward questions, the minimum response must explicitly state:

> The percentages are calculated on **[gross order value / eligible order value / received affiliate commission]**. The 4% example allocates **[exact customer points share]**, **[exact owner category/share]**, and **[exact platform operating or reserve share]**. The confirmation period is **[fixed / partner-specific]**, and points become usable under **[approved condition]**.

Until this statement and the remaining approvals are recorded, no reward ledger, ranking algorithm, gift automation, or partner activation should be built from the example alone.
