# Enterprise Audit Report

**Generated:** 2026-08-21T22:00:43.763379+00:00
**Repository:** `sahilk267/Affiliates`
**Commit:** `f23a894`

## Discovery proof

The dynamic scan indexed **219 files** in **1155 directories** while excluding only `.git` internals and generated `audit/` outputs. It found **0 TypeScript**, **0 JavaScript**, **45 Markdown**, **2 JSON**, **3 automated test files**, and **0 Docker-related files**. The full per-file index is `audit/index.json`.

## Finding summary

| Severity | Count |
|---|---:|
| CRITICAL | 0 |
| HIGH | 0 |
| MEDIUM | 0 |
| INFO | 0 |

## Findings

| ID | Severity | Category | Title | Location | Priority |
|---|---|---|---|---|---|


## Release conclusion

Release is blocked until the P0 issues are remediated and verified with a clean database initialization, authenticated API tests, consumer authentication tests, reward ledger tests, and CI that fails on test/migration errors.

See the sibling audit files for API, database, security, frontend, backend, AI, testing, documentation, code quality, architecture, dependencies, and release-readiness details.


## Calculated release score

**Release score:** **80.31/100** across 13 measurable categories, using the explicit control table in `audit/release-score.json`. Categories without repository/runtime evidence are recorded as **NOT MEASURABLE**, not converted into invented percentages.
