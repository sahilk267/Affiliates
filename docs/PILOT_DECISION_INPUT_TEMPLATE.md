# ZenithSoles Affiliates — Pilot Decision Input Template

**Status:** Draft — owner input required

> This template must be completed by the product/release owner before Phase 1 of `DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md` starts. Blank fields are **Unknown**; an AI agent must not infer or fill them.

## 1. Pilot identity

| Field | Owner input |
|---|---|
| Pilot name | `[REQUIRED]` |
| Product owner | `[REQUIRED]` |
| Pilot/release owner | `[REQUIRED]` |
| Security owner | `[REQUIRED]` |
| Finance/payout owner | `[REQUIRED]` |
| Target environment | `staging URL required; production is not permitted for certification` |
| Planned pilot start | `[REQUIRED]` |
| Planned pilot end | `[REQUIRED]` |
| Approval record/link | `[REQUIRED]` |

## 2. Business model decision

Select exactly one initial model and record the decision owner. The current owner-provided direction is recorded as a draft selection; it does not replace the required owner sign-offs or policy approvals.

- `[x]` Consumer affiliate comparison with post-confirmation reward points
- `[ ]` Consumer cashback and rewards
- `[ ]` B2B merchant affiliate tracking/reconciliation
- `[ ]` Hybrid model
- Decision owner: `[REQUIRED]`
- Decision date: `[REQUIRED]`
- Rationale: Users compare external merchant offers in one place, use referral links to reach the merchant, and receive reward points only after partner confirmation.

## 3. Target market decision

| Field | Owner input |
|---|---|
| Primary niche/category | `Owner direction: top 100 products across platforms and Google based on research; bounded initial category and exact “top 100” definition remain required.` |
| Target customer segment | `Owner direction: all online users; measurable first-pilot segment remains required.` |
| Geography/language scope | `[REQUIRED]` |
| Primary user problem | `Users currently visit multiple applications to compare prices and discounts; ZenithSoles should simplify discovery in one place.` |
| Why ZenithSoles is different | `Cross-platform product intelligence, price comparison, permitted price history, tracked referral links, and post-confirmation reward points; formal positioning approval remains required.` |
| Out-of-scope audiences/categories | `Direct merchant checkout, fulfilment, and unapproved live data sources are out of scope for the current external-affiliate model; owner confirmation remains required.` |
| Evidence source | `[REQUIRED — interviews, approved research, or owner decision]` |

## 4. Initial partner decision

List only partners with verified approval or explicitly mark them as prospects. Do not mark a prospect as active.

| Partner/network | Status: prospect/approved staging/approved pilot | Contract/source | Technical owner | Required certification |
|---|---|---|---|---|
| Amazon | `Direct API candidate; Creators API onboarding and approval pending` | `audit/phase1-partner-research-2026-08-24.md` | `[REQUIRED]` | catalog / link / conversion / reward |
| Flipkart | `Direct API candidate; registered-affiliate approval and credentials pending` | `audit/phase1-partner-research-2026-08-24.md` | `[REQUIRED]` | product / offer / link / report / reward |
| `[REQUIRED — third partner]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | conversion / points / payout |

## 5. Reward and financial policy inputs

These values must come from approved partner terms or a signed product/finance decision. Do not use placeholders in a live calculation.

| Policy | Approved value/decision | Source or approver |
|---|---|---|
| Reward basis | `Owner direction: received affiliate commission after partner confirmation; gross/net deductions and reversal treatment remain pending.` | `Owner message; finance approval required` |
| Customer allocation and points rule | `Owner direction: 40% of received affiliate commission to customer points; 10 points = ₹1 for voucher redemption; formal approval pending.` | `Owner message; finance/voucher approval required` |
| Referral eligibility | `[REQUIRED]` | `[REQUIRED]` |
| Referral reward rule | `[REQUIRED]` | `[REQUIRED]` |
| Pending-to-confirmed condition | `Owner estimate: approximately two months because partner confirmation/payment may take 2–3 months; partner-specific rule required.` | `[REQUIRED — partner terms/approver]` |
| Reversal/return treatment | `[REQUIRED]` | `[REQUIRED]` |
| Minimum withdrawal | `[REQUIRED]` | `[REQUIRED]` |
| Payout method and fee | `[REQUIRED]` | `[REQUIRED]` |
| Settlement timeline | `[REQUIRED]` | `[REQUIRED]` |
| Fraud review/reserve policy | `[REQUIRED]` | `[REQUIRED]` |

## 6. Pilot measurement contract

Define thresholds before the pilot begins. If the owner has not approved a threshold, record `Unknown` and do not claim a go/no-go result.

| Metric | Definition | Target/threshold | Data source | Owner |
|---|---|---|---|---|
| Confirmed commission per order | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` |
| Reward cost per confirmed order | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` |
| Contribution margin | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` |
| 30-day repeat purchase | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` |
| Missing-reward or attribution ticket rate | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` |
| Fraud/dispute rate | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` |
| Settlement time | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` | `[REQUIRED]` |

## 7. Explicit approval and stop conditions

### Approved before Phase 1

- `[ ]` One niche and one target customer segment approved.
- `[ ]` Consumer, B2B, or hybrid model approved.
- `[ ]` Pilot and release owners assigned.
- `[ ]` Three-to-five partner targets identified; approved staging access is separately recorded.
- `[ ]` Reward, referral, reversal, fraud, and payout policies assigned for approval.
- `[ ]` Pilot metrics and thresholds approved.

### Automatic stop conditions

- Any required field remains blank or only has an invented/default value.
- A partner is described as active without written approval or staging certification.
- Production credentials, production URLs, real customer data, or real funds are proposed for testing.
- Commission, reward, payout, reversal, CAC, LTV, or capacity values are assumed rather than sourced.
- The business model or primary niche changes without a new owner decision record.

## 8. Owner sign-off

| Role | Name | Decision | Date | Signature/link |
|---|---|---|---|---|
| Product owner | `[REQUIRED]` | approve / revise / block | `[REQUIRED]` | `[REQUIRED]` |
| Release owner | `[REQUIRED]` | approve / revise / block | `[REQUIRED]` | `[REQUIRED]` |
| Security owner | `[REQUIRED]` | approve / revise / block | `[REQUIRED]` | `[REQUIRED]` |
| Finance/payout owner | `[REQUIRED]` | approve / revise / block | `[REQUIRED]` | `[REQUIRED]` |

## Current status

**Blocked — owner inputs and approvals are incomplete.** The owner has supplied the external-merchant comparison direction and post-confirmation 40/40/20 reward direction, but this document still requires bounded scope, partner approvals, data permissions, detailed reversal/voucher/gift rules, numeric metrics, named owners, dates, and sign-offs. It is a request for factual inputs, not permission for an AI agent to choose the business strategy.
