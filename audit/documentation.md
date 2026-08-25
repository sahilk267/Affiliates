# Documentation Audit

The documentation set was reconciled on 2026-08-25 to reduce contradictory or stale guidance for future agents and release owners.

| Classification | Count | Location or authority |
|---|---:|---|
| Active Markdown documents | 44 | Repository root, `.cursor/`, `.github/`, `docs/`, and active `audit/` records |
| Archived Markdown documents | 22 | `docs/archive/legacy/` |
| Active implementation source of truth | 1 | `CURRENT_PROJECT_STATUS.md` |
| Active execution plan | 1 | `DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md` |
| Current commercial gate | 2 | `docs/PHASE1_REMAINING_DECISIONS.md`, `audit/phase1-gate.json` |
| Current partner research | 1 | `audit/phase1-partner-research-2026-08-24.md` |
| Current catalog foundation evidence | 2 | `docs/API_INDEPENDENT_CATALOG_FOUNDATION.md`, `audit/phase3-foundation.json` |

The archived set contains obsolete Laravel 10/bootstrap notes, pre-Laravel-12 plans, stale implementation summaries, superseded readiness snapshots, an old quick-start checklist, and duplicate generic rule READMEs. They remain in Git for history but are not current instructions, approvals, partner evidence, financial policy, or production-readiness claims. The archive index is `docs/archive/README.md`.

The active `CURRENT_PROJECT_STATUS.md` is the primary project-status source, while `README.md` provides the repository overview. Together they describe the owner-confirmed external-merchant comparison and post-confirmation reward-points direction, the API-pending development policy, the source-agnostic catalog foundation, current validation commands, and the staging/production boundary. They do not claim that partner APIs, price feeds, merchant permissions, settlement terms, vouchers, gifts, or production readiness are available.

Documentation checks are enforced by `tools/validate_release_contracts.py`, which requires the current contracts, runbooks, phase documents, foundation evidence, and guardrail tests. Broken links to archived or nonexistent status documents must not be reintroduced.
