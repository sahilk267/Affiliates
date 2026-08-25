# ZenithSoles Affiliates — Staging Readiness Report

## Executive result

The current staging-readiness evidence is complete for the repository-level controls. The repository includes a non-mutating partner contract checker, a deterministic payout reconciliation checker, a staging acceptance record, an API-independent catalog and comparison preview foundation, and a guarded local fixture seeder. The latest local verification passed with **zero Composer advisories**, clean migrations, PHP lint, `git diff --check`, and **19 tests with 91 assertions**. CI enables PCOV, generates Clover coverage, and uploads a per-PHP-version coverage artifact.

The refreshed dynamic audit scan reports **0 unresolved evidence-based findings** and a calculated release score of **80.31/100** across measurable repository controls. The security baseline now includes globally registered browser-hardening headers, covered by the feature suite, and the testing baseline now includes CI coverage artifact generation. Performance capacity and AI safety remain explicitly **NOT MEASURABLE** rather than being assigned invented scores. The score and issue inventory are stored in `audit/release-score.json` and `audit/issues.json`.

No production credentials, live partner systems, real payouts, or customer data were used. The valid financial mutation scenarios remain explicitly gated behind staging credentials, staging fixtures, and `--allow-mutations`.

## New staging controls

| Control | Implementation | Purpose |
|---|---|---|
| Partner contract checker | `tools/partner_contract_check.py` | Verifies health, invalid HMAC rejection, expired timestamp rejection, signed validation failure, and optionally gated valid conversion/replay behavior |
| Payout reconciliation checker | `tools/reconcile_payouts.py` | Compares platform/provider CSV exports for missing, unexpected, duplicate, amount, status, and external-reference mismatches |
| Staging acceptance record | `docs/STAGING_ACCEPTANCE_RECORD.md` | Captures release identity, automated gates, partner certification, payout controls, observability, rollback, and sign-off evidence |
| Operations runbook | `docs/RELEASE_OPERATIONS_RUNBOOK.md` | Defines migration rehearsal, secret rotation, partner certification, payout reconciliation, rollback, and alerting procedures |
| Pilot decision gate | `tools/validate_pilot_decision_inputs.py` and `audit/phase1-gate.json` | Blocks Phase 1 business implementation until the remaining category, partner, policy, metric, owner, and sign-off inputs are approved |
| Owner-action package | `docs/OWNER_ACTION_PACKAGE.md` and `audit/owner-action-package.json` | Maps each pending Phase 1 input to acceptable evidence, stop conditions, and completion checks |
| API-independent catalog and comparison preview | `docs/API_INDEPENDENT_CATALOG_FOUNDATION.md`, `audit/phase3-foundation.json`, `database/seeders/ComparisonPreviewSeeder.php`, `config/comparison.php` | Provides local snapshot/history/comparison contracts, synthetic fixtures, and disabled reward switches without claiming external API or merchant data |

## Local evidence

| Check | Result | Artifact |
|---|---|---|
| Python tool compilation | Passed | `python3 -m py_compile` for all repository Python tools |
| Sample payout reconciliation | Passed; 2 matched IDs, 0 exceptions | `audit/payout-reconciliation-final-2026-08-20.json` |
| Partner negative-contract checks | Passed: health 200, invalid signature 401, expired timestamp 401, signed malformed payload 422 | `audit/partner-contract-check-final-2026-08-20.json` |
| Read-only smoke | Passed: 5/5 requests successful, database connected | `audit/staging-smoke-check-final-2026-08-20.json` |
| PHP lint | Passed | Final verification session |
| Clean SQLite migration | Passed through all migrations | Existing migration evidence and final verification session |
| PHPUnit | **19 tests, 91 assertions, all passing** | Latest local verification session; dated earlier-count evidence is historical |
| Composer audit | Passed with 0 advisories | `audit/composer-audit-laravel12-2026-08-20.json` |
| Whitespace integrity | Passed | `git diff --check` |
| Release contract validator | Passed | `tools/validate_release_contracts.py` |
| Phase 1 pilot-input validator | Passed in report-only mode; strict gate correctly blocked | `audit/phase1-gate.json` |
| Dynamic audit refresh | Passed; 0 unresolved findings | `audit/issues.json`, `audit/release-score.json` |

The partner checker’s valid mutation section is intentionally skipped in the local evidence because no live staging credentials or approved staging fixtures were supplied. The current owner-action package is a documentation handoff only; it does not authorize staging mutations or production activity.
 That is a control boundary, not a failed test. The Phase 1 pilot-input validator remains blocked because only the model direction and some commercial context are captured; category scope, partner approvals, data permissions, ranking weights, detailed reward/voucher/reversal/gift policy, metrics, owners, dates, and sign-offs remain incomplete.

## Staging execution procedure

The staging owner should copy `docs/STAGING_ACCEPTANCE_RECORD.md`, populate the release identity, and run the read-only checks first:

```bash
python3 tools/staging_smoke_test.py \
  --base-url "$STAGING_BASE_URL" \
  --requests 20 \
  --concurrency 4 \
  --timeout 5

python3 tools/partner_contract_check.py \
  --base-url "$STAGING_BASE_URL" \
  --timeout 5
```

After security and partner owners approve the test window, the owner may run the gated valid conversion/replay certification with a dedicated staging click and event identity:

```bash
AFFILIATE_API_KEY="$STAGING_AFFILIATE_API_KEY" \
AFFILIATE_API_SECRET="$STAGING_AFFILIATE_API_SECRET" \
python3 tools/partner_contract_check.py \
  --base-url "$STAGING_BASE_URL" \
  --allow-mutations \
  --click-id "$STAGING_CLICK_ID" \
  --partner-event-id "staging-cert-$(date +%s)"
```

The test must be pointed only at staging. Its conversion event must use a disposable fixture and a zero-value `other` event unless the partner certification plan explicitly requires a non-zero test purchase. The resulting conversion, commission, and reward identifiers must be reconciled and the staging data must be removed or marked as certification data according to the environment’s retention policy.

For payout reconciliation, export the platform and provider records using the agreed schema and run:

```bash
python3 tools/reconcile_payouts.py \
  staging/platform-payout-export.csv \
  staging/provider-payout-export.csv \
  --output staging/payout-reconciliation.json
```

A non-zero exit status blocks the payout batch. Every exception must be assigned to an owner and resolved or explicitly accepted by the payout owner before sign-off.

## Controlled-staging execution package

The local-versus-staging boundary is documented in `docs/CONTROL_EXECUTION_MATRIX.md`. The dated credential-free evidence bundle is under `audit/credential-free/` and is historical. Current repository-level evidence is recorded in `audit/RELEASE_READINESS_INVENTORY.md`, `audit/reports/latest.md`, and `audit/phase3-foundation.json`; the latest local suite has 19 passing tests with 91 assertions.

The blocker completeness check reports **14 total staging blockers, 0 closed, and 14 open or incomplete**. This is expected for an unassigned handoff template and means production approval remains blocked. The result is archived in `audit/blocker-completeness/staging-blocker-status.json`.

## Final handoff artifacts

The final staging handoff is captured in `FINAL_STAGING_HANDOFF_CHECKLIST.md`, with external controls tracked in `STAGING_BLOCKER_REGISTER.md`. The repository metadata decision is deliberately documented in `docs/LICENSE_DECISION_RECORD.md`; no SPDX license value has been guessed.

## Remaining production blockers

The local implementation is ready for staging, but production approval still requires actual environment work. Partners must certify their raw-body HMAC implementation, retry handling, idempotency persistence, and webhook contracts. The deployment team must validate PHP 8.2+, secret-manager injection and rotation, representative-schema migration rehearsal, rollback compatibility, centralized log delivery, alert thresholds, and on-call ownership.

The payout owner must complete a provider-backed reconciliation dry run and confirm that no real funds are transferred during certification. GeoIP provider selection remains subject to privacy and retention approval. Formal capacity testing requires a staging traffic model, representative data, monitoring, and an agreed stop condition; the bounded smoke harness is not a capacity certification.

## References

1. [`LARAVEL12_UPGRADE_REPORT.md`](LARAVEL12_UPGRADE_REPORT.md), Laravel 12 dependency upgrade and security verification.
2. [`audit/phase1-partner-research-2026-08-24.md`](audit/phase1-partner-research-2026-08-24.md), partner/API research and source boundaries.
3. [`docs/API_INDEPENDENT_CATALOG_FOUNDATION.md`](docs/API_INDEPENDENT_CATALOG_FOUNDATION.md), source-agnostic catalog and ranking foundation.
4. [`PARTNER_INTEGRATION_CONTRACT.md`](PARTNER_INTEGRATION_CONTRACT.md), partner API and idempotency contract.
5. [`docs/RELEASE_OPERATIONS_RUNBOOK.md`](docs/RELEASE_OPERATIONS_RUNBOOK.md), deployment and operational controls.
6. [`docs/STAGING_ACCEPTANCE_RECORD.md`](docs/STAGING_ACCEPTANCE_RECORD.md), staging evidence and sign-off template.
7. [`tools/partner_contract_check.py`](tools/partner_contract_check.py), partner certification checker.
8. [`tools/reconcile_payouts.py`](tools/reconcile_payouts.py), payout reconciliation checker.
9. [`tools/staging_smoke_test.py`](tools/staging_smoke_test.py), bounded read-only smoke and latency harness.
