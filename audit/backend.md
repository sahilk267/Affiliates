# Backend Audit

The backend contains 7 controllers, 7 services, and 15 top-level domain models. Controllers use dependency injection in several places, but large controllers also contain direct Eloquent queries, duplicated product commission logic, and direct controller construction in admin API test helpers.

The principal remaining backend considerations are direct Eloquent access without a repository abstraction, environment-specific GeoIP/provider adapters, and runtime performance behavior that requires staging measurement. Conversion and payout orchestration is transaction-scoped through shared services. Complexity metrics are in `statistics.json`; they are static heuristics, not runtime profiled values.
