# Archived Documentation

This directory contains superseded planning, status, and quick-start documents retained for historical traceability. **Archived documents are not current instructions, release evidence, owner approvals, partner contracts, or production-readiness claims.** Read `CURRENT_PROJECT_STATUS.md` at the repository root first for the current project state.

The primary project-status source is the repository root `CURRENT_PROJECT_STATUS.md`. The repository overview is `README.md`; supporting current sources are the contracts and runbooks, `DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md`, Phase 1 decision documents under `docs/`, and dated evidence records under `audit/`.

## Archive rules

A document is moved here when it contains an obsolete framework/version assumption, stale test or readiness result, an outdated implementation plan, duplicate content, or an unapproved business/partner assertion that could mislead a future agent. Files are moved rather than deleted so their historical context remains available. New work must not update an archived file instead of the current source of truth.

## Archived files

| File | Reason |
|---|---|
| `FINAL_STATUS.md` | Superseded status snapshot; current status is maintained in the active readiness and phase-gate documents. |
| `IMPLEMENTATION_COMPLETE.md` | Historical completion claim that predates the current evidence-gated plan. |
| `IMPLEMENTATION_PROGRESS.md` | Historical progress report with stale scope and verification references. |
| `FINAL_IMPLEMENTATION_SUMMARY.md` | Previous remediation-batch summary; not the current implementation baseline. |
| `NEXT_RELEASE_READINESS_REPORT.md` | Superseded pre-Laravel-12 readiness report. |
| `PROJECT_STATUS_REPORT.md` | Historical project status snapshot with unverified or outdated claims. |
| `PRIORITIZATION_ROADMAP.md` | Superseded roadmap replaced by the detailed anti-hallucination phase plan. |
| `PHASE_WISE_IMPLEMENTATION_PLAN.md` | Superseded earlier phase plan replaced by the detailed evidence-gated plan. |
| `QUICK_START_CHECKLIST.md` | Superseded checklist containing old authentication/product assumptions and stale estimates. |
| `NOTES.md` | Old Laravel 10/bootstrap notes and local-running claims; not a current status source. |
| `complete-project-rules/*` | Obsolete duplicate generic rule set; the active rule source is `.cursor/rules/`, and the full legacy set is preserved under `docs/archive/legacy/complete-project-rules/`. |

Use `git log --follow -- <path>` to trace the history of any archived file.
