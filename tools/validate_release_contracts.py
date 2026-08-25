#!/usr/bin/env python3
"""Validate repository release-contract and documentation invariants."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REQUIRED_FILES = [
    "CURRENT_PROJECT_STATUS.md",
    "API_SECURITY_CONTRACT.md",
    "PARTNER_INTEGRATION_CONTRACT.md",
    "LARAVEL12_UPGRADE_REPORT.md",
    "STAGING_READINESS_REPORT.md",
    "docs/RELEASE_OPERATIONS_RUNBOOK.md",
    "docs/STAGING_ACCEPTANCE_RECORD.md",
    "docs/architecture.md",
    "docs/openapi.yaml",
    "docs/adr/0001-atomic-financial-transitions.md",
    "docs/LICENSE_DECISION_RECORD.md",
    "FINAL_STAGING_HANDOFF_CHECKLIST.md",
    "docs/CONTROL_EXECUTION_MATRIX.md",
    "docs/STAGING_OWNER_EXECUTION_GUIDE.md",
    "STAGING_BLOCKER_REGISTER.md",
    "DETAILED_PHASE_WISE_IMPLEMENTATION_PLAN.md",
    "docs/PILOT_DECISION_INPUT_TEMPLATE.md",
    "docs/PHASE1_REMAINING_DECISIONS.md",
    "docs/PHASE1_OWNER_AND_TIMELINE_PROPOSAL.md",
    "docs/API_INDEPENDENT_CATALOG_FOUNDATION.md",
    "docs/OWNER_ACTION_PACKAGE.md",
    "audit/owner-action-package.json",
    "audit/phase3-foundation.json",
    "audit/documentation-cleanup-2026-08-25.json",
    "docs/archive/README.md",
    "tools/validate_pilot_decision_inputs.py",
    "tools/test_validate_pilot_decision_inputs.py",
    "tools/validate_staging_blockers.py",
    "tools/partner_contract_check.py",
    "tools/reconcile_payouts.py",
    "tools/staging_smoke_test.py",
    "config/comparison.php",
    "app/Services/Contracts/ProductSourceAdapter.php",
    "database/seeders/ComparisonPreviewSeeder.php",
]
REQUIRED_OPENAPI_PATHS = [
    "/api/health:",
    "/api/affiliate/click:",
    "/api/affiliate/conversion:",
    "/api/points/credit:",
    "/api/referral/track:",
]


def main() -> int:
    errors: list[str] = []
    for relative in REQUIRED_FILES:
        path = ROOT / relative
        if not path.is_file() or path.stat().st_size == 0:
            errors.append(f"missing or empty required artifact: {relative}")

    composer = json.loads((ROOT / "composer.json").read_text(encoding="utf-8"))
    if composer.get("require", {}).get("php") != "^8.2":
        errors.append("composer.json must target PHP ^8.2 for Laravel 12")
    if composer.get("require", {}).get("laravel/framework") != "^12.0":
        errors.append("composer.json must target Laravel ^12.0")
    if composer.get("require-dev", {}).get("phpunit/phpunit") != "^11.0":
        errors.append("composer.json must target PHPUnit ^11.0")

    openapi = (ROOT / "docs/openapi.yaml").read_text(encoding="utf-8")
    for path_marker in REQUIRED_OPENAPI_PATHS:
        if path_marker not in openapi:
            errors.append(f"OpenAPI contract missing path marker: {path_marker}")

    readme = (ROOT / "README.md").read_text(encoding="utf-8")
    stale_links = re.findall(r"`?/?docs/[^` )]+", readme)
    for reference in stale_links:
        relative = reference.lstrip("/")
        if relative.startswith("docs/") and not (ROOT / relative).exists():
            errors.append(f"README references missing documentation: {reference}")
    if "Hostinger Deployment Notes" in readme:
        errors.append("README contains superseded Hostinger Deployment Notes heading")

    if errors:
        print("Release contract validation failed:")
        print("\n".join(f"- {error}" for error in errors))
        return 1

    print(json.dumps({
        "status": "passed",
        "required_files": len(REQUIRED_FILES),
        "openapi_paths_checked": len(REQUIRED_OPENAPI_PATHS),
        "framework_constraints": {
            "php": composer["require"]["php"],
            "laravel": composer["require"]["laravel/framework"],
            "phpunit": composer["require-dev"]["phpunit/phpunit"],
        },
    }, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
