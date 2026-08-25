# ZenithSoles Affiliates — Owner Action Package

**Status:** Action required — Phase 1 remains blocked

**Purpose:** This document is the owner-facing execution checklist for closing the current Phase 1 commercial-input gate. It does not approve a partner, select a category, create a financial rule, authorize staging mutations, or approve production. The owner must provide the missing facts and approval evidence in `docs/PILOT_DECISION_INPUT_TEMPLATE.md`.

> **Safety rule:** A missing value remains missing. No AI agent or developer may replace a required owner decision with a guessed category, partner status, commission basis, confirmation period, reward rule, metric threshold, owner, date, approval, credential, or production setting.

## 1. Current measured gate

The report-only validator currently records one draft model direction, **32 required template fields**, and **4 owner sign-off rows** still pending. The strict validator is expected to return a non-zero exit code. Production approval remains `not_approved` regardless of the Phase 1 result.

| Gate item | Current status | Required owner action |
|---|---|---|
| Business model | Draft direction captured: consumer affiliate comparison with post-confirmation reward points | Confirm, revise, or block the wording and record the decision owner/date |
| Bounded pilot scope | Not selected | Select the first category set, geography/language, measurable audience, and pilot window |
| Partner shortlist | Candidates documented; no activation approval | Select 3–5 targets and attach current approval/source evidence, or explicitly mark each as prospect |
| Data permissions | Not approved | Identify permitted API/feed/network sources and document fields, retention, and comparison rights |
| Ranking policy | Direction only | Approve weights, normalization, tie-breaking, freshness, and commercial-influence disclosure |
| Reward/financial policy | Owner direction captured; formal policy pending | Approve denominator, deductions, confirmation, reversal, voucher, payout, fraud, and gift treatment |
| Pilot metrics | Not defined | Set numeric definitions, thresholds, sources, owners, and go/no-go rules |
| Accountable owners | Names not supplied | Name Product, Release, Security, and Finance/Payout owners; assign technical/data/operations roles as needed |
| Sign-offs | 0 of 4 complete | Record explicit `approve`, date, and signature/link for all four required roles |

## 2. Inputs already recorded and not to be re-invented

The owner has already described the following direction. These statements are recorded as **proposed inputs**, not approved production policy:

| Area | Recorded direction | Boundary |
|---|---|---|
| Customer problem | Online shoppers visit multiple applications to compare product prices and discounts | This does not establish demand, conversion, or market size |
| Platform role | Aggregate external merchant offers and send the user to the merchant through a referral link | ZenithSoles is not the checkout marketplace for this pilot |
| Product value | Search, comparison, permitted price history, and transparent offer ordering | Product matching, source permission, freshness, and ranking policy remain open |
| Reward model | Points only after partner/order confirmation rather than immediate cashback | Confirmation and reversal are partner-specific until evidenced |
| Allocation direction | 40% customer points, 40% owner/business share, 20% platform scaling/maintenance reserve, based on received commission as described by the owner | Finance/Payout must approve the denominator, deductions, accounting, liability, and reversals |
| Points direction | 10 points = ₹1 for voucher redemption | Voucher provider, expiry, limits, liability, and approval remain open |
| Channels | WhatsApp, Telegram, creators, SEO, communities, Facebook, and Instagram | Pilot channel priority, budget, consent, and thresholds remain open |
| Future scope | First-party products may be added later | Excluded from the current external-affiliate pilot |

## 3. Owner completion sequence

### Step A — Complete the pilot decision template

Edit `docs/PILOT_DECISION_INPUT_TEMPLATE.md` directly. Replace every `[REQUIRED]` marker with an owner-supplied answer or an explicitly approved decision record. Do not change the selected model checkbox unless the owner makes a new decision. If the owner changes the model, category, or material policy, record a new decision rationale and date rather than silently editing history.

### Step B — Attach evidence for each decision

Every material decision must identify its source or approver. Acceptable evidence includes a signed owner decision, current partner or network documentation, an approved data-permission record, a Finance/Payout policy decision, a Security/Privacy review, or a sanitized staging record after Phase 1 approval. A public merchant page, search result, creator page, or unverified API mention is not proof of access, permission, attribution, settlement, or price-history rights.

### Step C — Complete the four mandatory sign-offs

The template must contain one row each for Product owner, Release owner, Security owner, and Finance/payout owner. Each row must contain a named person, the literal decision `approve` only when that role has actually approved, an approval date, and a signature or durable approval link. `TBD`, blank, inferred, or verbal-only values do not close the gate.

### Step D — Run and preserve the gate evidence

Run the following commands from the repository root and preserve their outputs with the release record:

```bash
python3 tools/validate_pilot_decision_inputs.py
python3 tools/validate_pilot_decision_inputs.py --require-approved
```

The report-only command should return status `ready_for_phase_1` only after all required fields and sign-offs are complete. The strict command must then return exit code `0`. Until that happens, the expected status is blocked and no live partner or reward activation is permitted.

## 4. Decision-by-decision response checklist

| ID | Owner must provide | Minimum evidence to record | Gate remains blocked if |
|---|---|---|---|
| OA-01 | Pilot name, Product owner, Release owner, Security owner, Finance/Payout owner | Named people and durable approval record | Any required identity, date, or approval field is missing |
| OA-02 | Planned pilot start/end, staging URL, target geography/language | Approved test-window record; never a production URL for certification | Dates or staging target are absent or unapproved |
| OA-03 | Exact model decision, decision owner/date, rationale | Owner decision record | Draft model is treated as approved without sign-off |
| OA-04 | First bounded category set and exact “top 100” definition | Approved research or owner decision naming source, geography, period, refresh, and deduplication | “All products” or “top 100” is used without a testable definition |
| OA-05 | Measurable first-pilot audience, out-of-scope audiences, and primary user problem | Interview, approved research, or owner decision | Broad “all online users” is treated as a measurable pilot segment |
| OA-06 | Initial 3–5 partner targets and status for each | Current contract/program/API evidence; otherwise mark prospect | Any candidate is labelled active without approval and certification |
| OA-07 | Source for price, availability, rating, search demand, and price history | Data-permission record covering fields, retention, license/terms, and deletion | Scraping, copying, or ingestion is implemented without permission |
| OA-08 | Ranking weights, normalization, freshness, tie-breaking, and commercial disclosure | Approved ranking policy and test cases | Referral margin silently overrides a cheaper offer or missing data is treated favourably |
| OA-09 | Reward denominator and eligible commission basis | Signed Product/Finance/Payout policy plus partner terms | The 4% example is used as a live calculation |
| OA-10 | 40/40/20 allocation treatment, deductions, accounting, and liability | Finance/Payout approval and reconciliation definition | Owner direction is presented as finalized financial policy |
| OA-11 | Partner-specific pending, confirmed, returned, reversed, cancelled, and usable states | Current partner/network lifecycle terms | A universal two-month promise is used for every partner |
| OA-12 | Referral eligibility, referral reward, self-referral/fraud rules | Approved referral and fraud policy | Eligibility or reward is promised without policy approval |
| OA-13 | Minimum withdrawal, payout method/fees, settlement timeline, and reserve policy | Approved payout policy and provider terms | Payout or reserve liability is assumed |
| OA-14 | Gift eligibility, selection, budget/cap, frequency, disclosure, return, and fraud treatment | Signed gift/marketing decision and budget owner | Gifts are promised or distributed without an approved cap and rule |
| OA-15 | Numeric pilot metric definitions, thresholds, sources, and owners | Baseline measurement plan and go/no-go decision rule | Success, revenue, margin, or retention is claimed without thresholds |
| OA-16 | Four explicit role approvals | Completed sign-off table with dates and links | Any sign-off is blank, `TBD`, `revise`, `block`, or not durable |

## 5. Financial-policy clarification that must be answered explicitly

The owner and Finance/Payout approver must complete this statement without choosing a value by inference:

> The percentages are calculated on **[gross order value / eligible order value / received affiliate commission]**. The 4% example allocates **[exact customer points share]**, **[exact owner/business category or share]**, and **[exact platform operating or reserve share]**. Deductions before allocation are **[list or none]**. The confirmation period is **[fixed / partner-specific]**, points become usable under **[approved condition]**, and a return/reversal is handled by **[approved rule]**.

Until this statement is approved, the comparison preview remains read-only and the reward, voucher, gift, and financial activation paths remain disabled or unapproved. No real funds or customer liability may be created from the example alone.

## 6. Partner and staging evidence boundary

Phase 1 completion does not itself activate a partner. After owner approval, each selected partner still requires current technical terms, data permission, secure staging credentials, field mapping, negative authentication tests, read-only discovery evidence, attribution certification, idempotency/replay evidence, reversal handling, and reconciliation. Valid financial mutations require explicit staging authorization, disposable identifiers, and the documented mutation controls. Production credentials, real funds, and real customer data remain prohibited for certification.

The existing local comparison preview is safe to demo because it uses synthetic `example.test` fixtures, timestamped snapshots, unavailable-value handling, external-click intent only, and disabled comparison rewards/vouchers/gifts. It is not evidence of any merchant’s live catalogue, API, commission, stock, rating, settlement, or reward behavior.

## 7. Definition of done for the owner action

The owner-action package is complete only when all of the following are true:

1. `docs/PILOT_DECISION_INPUT_TEMPLATE.md` contains no unresolved `[REQUIRED]` markers.
2. Exactly one pilot model is selected and its rationale, owner, and date are recorded.
3. The first bounded category, measurable audience, geography/language, pilot dates, and staging URL are approved.
4. Each initial partner is explicitly marked prospect, approved staging, or approved pilot with current evidence; no unverified partner is called active.
5. Data permissions, ranking disclosure, reward/reversal/voucher/gift/payout policy, fraud controls, and metric thresholds are recorded with approvers.
6. All four mandatory sign-off rows explicitly say `approve` with durable dates and links.
7. The report-only validator returns `ready_for_phase_1` and the strict validator returns exit code `0`.
8. The Phase 1 gate artifact and current project-status source are refreshed, while production remains separately blocked until staging evidence is complete.

## 8. Authoritative references

| Document | Use |
|---|---|
| `CURRENT_PROJECT_STATUS.md` | Current implementation, blockers, and non-claims |
| `docs/PILOT_DECISION_INPUT_TEMPLATE.md` | The actual owner input form to complete |
| `docs/PHASE1_REMAINING_DECISIONS.md` | Decision rationale and stop conditions |
| `docs/PHASE1_OWNER_AND_TIMELINE_PROPOSAL.md` | Proposed role sequence; not an approval |
| `audit/phase1-owner-input-capture.json` | Owner-supplied facts versus unresolved ambiguities |
| `audit/phase1-gate.json` | Machine-readable current Phase 1 result |
| `STAGING_BLOCKER_REGISTER.md` | External staging blockers after Phase 1 |
| `docs/STAGING_OWNER_EXECUTION_GUIDE.md` | Later staging evidence workflow |

This package is a documented request for owner inputs. It is not an approval record and does not authorize partner activation, real rewards, financial mutations, staging mutations, or production deployment.
