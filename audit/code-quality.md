# Code Quality Audit

## Measured static metrics

- PHP method definitions scanned: 418
- Average cyclomatic complexity heuristic: 6.95
- Average maintainability index heuristic: 44.04
- Duplicate content groups: 1
- Duplicate filename groups: 12
- Dead-code candidates: 83 (heuristic; not proof of dead code)

The largest PHP files and highest-complexity files are listed in `statistics.json`. Runtime memory leaks, thread-level race conditions, and blocking I/O are **NOT MEASURABLE** from static source alone. The main maintainability concerns are oversized controllers, duplicated product commission logic, direct model queries, and stale documentation.
