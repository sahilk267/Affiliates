# Phase 1 Owner and Timeline Proposal — ZenithSoles Affiliates

**Status:** Proposed for owner approval; no person, date, or approval is asserted as confirmed.

This proposal assigns responsibilities by role only. The release owner must replace each `TBD` with a named person and approve the dates before Phase 1 can close. The dates below are a suggested sequence anchored to **2026-08-24**; they are not a committed launch schedule.

## Proposed role ownership

| Role | Proposed responsibility | Required approval or evidence | Named person |
|---|---|---|---|
| Business/Product owner | Approve the initial category scope, customer segment, model wording, value proposition, ranking disclosure, gift concept, and out-of-scope boundaries. | Signed positioning and pilot decision record. | TBD |
| Release owner | Control phase gates, scope, release identity, staging window, and final readiness record. | Phase gate and staging acceptance record. | TBD |
| Affiliate integration owner | Onboard the first 3–5 partner targets; obtain partner/network acceptance; map product, offer, link, transaction, and report fields. | Partner approvals, sanitized field maps, and certification evidence. | TBD |
| Data/Privacy owner | Approve price, availability, search-demand, and history sources; review API/feed terms, retention, deletion, and comparison rights. | Data-source and privacy decision record. | TBD |
| Finance/Payout owner | Approve the 40% customer-points / 40% owner-business / 20% platform-scaling allocation on received affiliate commission; approve points-to-voucher conversion, settlement, reversal, gifts, and reconciliation rules. | Signed financial policy and reconciliation acceptance. | TBD |
| Security owner | Approve credential handling, network access, SubID/event identifiers, secret rotation, logging redaction, and partner security tests. | Security review and sanitized staging evidence. | TBD |
| Engineering owner | Implement source adapters, canonical product/offer records, price snapshots, ranking configuration, reward-state integration, tests, and observability within approved scope. | Pull request, test output, schema/contract evidence. | TBD |
| Operations/Support owner | Define support intake, missing-reward handling, channel operations, gift fulfilment process, incident escalation, and on-call coverage. | Support runbook, escalation roster, and drill record. | TBD |

## Proposed decision sequence

| Suggested window | Work | Accountable role | Entry condition | Exit evidence |
|---|---|---|---|---|
| 2026-08-24 to 2026-08-26 | Complete category, audience, model, ranking, reward, gift, and metric decisions. | Business/Product owner with Finance/Payout and Data/Privacy review. | Current owner direction and remaining-decisions checklist. | Completed pilot template with named owners and approval links. |
| 2026-08-27 to 2026-09-02 | Verify the first 3–5 partner routes and select direct versus intermediary integration for each. | Affiliate integration owner. | Completed partner shortlist. | Partner status table with current source documents and no unverified active labels. |
| 2026-09-03 to 2026-09-09 | Obtain technical/data contracts and sanitized examples. | Affiliate integration, Data/Privacy, and Security owners. | Partner/network acceptance. | Field maps, terms decisions, credential references, and retention decision. |
| 2026-09-10 to 2026-09-16 | Implement only the first approved source adapter and read-only product/offer ingestion. | Engineering owner. | Approved technical contract and test fixtures. | Pull request, schema tests, ingestion evidence, and rollback note. |
| 2026-09-17 to 2026-09-23 | Run read-only staging discovery, link, availability, ranking, and price-history checks. | Release, Security, Data/Privacy, and Affiliate integration owners. | Approved staging URL and disposable fixtures. | Sanitized staging acceptance record. |
| 2026-09-24 to 2026-09-30 | Run controlled attribution/reward certification only if staging credentials, test window, and mutation authorization exist. | Affiliate integration, Finance/Payout, Security, and Release owners. | All required staging inputs and explicit mutation authorization. | Conversion/reward/replay/reversal/reconciliation evidence. |

## Timeline controls

The suggested dates must be moved if a required input or approval is late. The agent must not compress the schedule by skipping partner terms, data-permission review, negative tests, reconciliation, or sign-off. A missing owner or evidence item changes the status to **Blocked** rather than creating a placeholder approval.

The date sequence does not authorize production activity. All valid mutation testing requires an approved staging URL, staging credentials, disposable identifiers, explicit authorization, and the documented mutation flag. No real funds or production credentials should be used for certification.

## Decisions still required from the owner

The owner must confirm:

1. the named person for each role;
2. whether the proposed date windows are acceptable;
3. the first bounded category scope under the top-100 discovery direction;
4. the first 3–5 partner targets and their current status;
5. the approved data/permission route for prices, availability, search demand, and price history;
6. the exact ranking weights and commercial-ranking disclosure;
7. the financial policy details listed in `docs/PHASE1_REMAINING_DECISIONS.md`;
8. the numeric pilot metric thresholds;
9. the staging URL and approved test window.

Until these are supplied and signed, the proposal remains **Draft — not an approval**.
