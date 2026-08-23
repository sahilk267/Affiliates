"""Validate the pilot decision template without inventing business inputs."""

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TEMPLATE = ROOT / "docs/PILOT_DECISION_INPUT_TEMPLATE.md"
REQUIRED_MARKER = "[REQUIRED]"
MODEL_OPTIONS = {
    "consumer": "Consumer cashback and rewards",
    "b2b": "B2B merchant affiliate tracking/reconciliation",
    "hybrid": "Hybrid model",
}


def required_lines(text: str) -> list[str]:
    return [
        line.strip()
        for line in text.splitlines()
        if REQUIRED_MARKER in line
    ]


def checked_models(text: str) -> list[str]:
    matches = re.findall(r"- \[x\]\s*(.+)", text, flags=re.IGNORECASE)
    return [label.strip() for label in matches if label.strip() in MODEL_OPTIONS.values()]


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Report or strictly enforce completion of the owner pilot decision template."
    )
    parser.add_argument(
        "--require-approved",
        action="store_true",
        help="Return non-zero unless required inputs are completed and all required owner approvals say approve.",
    )
    args = parser.parse_args()

    if not TEMPLATE.is_file():
        print(json.dumps({"status": "error", "error": "pilot decision template missing"}, indent=2))
        return 2

    text = TEMPLATE.read_text(encoding="utf-8")
    missing: list[str] = []
    required = required_lines(text)
    if required:
        missing.append(f"{len(required)} required fields still contain {REQUIRED_MARKER}")

    models = checked_models(text)
    if len(models) != 1:
        missing.append(f"exactly one approved pilot model must be selected; checked={len(models)}")

    approval_roles = ("Product owner", "Release owner", "Security owner", "Finance/payout owner")
    signoff_section = text.split("## 8. Owner sign-off", 1)[-1]
    approval_rows = [
        line.strip()
        for line in signoff_section.split("## Current status", 1)[0].splitlines()
        if line.startswith("|") and any(line.startswith(f"| {role} |") for role in approval_roles)
    ]
    unapproved_rows = [
        line for line in approval_rows
        if len([field.strip() for field in line.strip().strip("|").split("|")]) < 5
        or [field.strip() for field in line.strip().strip("|").split("|")][2].lower() != "approve"
    ]
    if len(approval_rows) != len(approval_roles):
        missing.append(f"all {len(approval_roles)} owner sign-off rows are required; found={len(approval_rows)}")
    elif unapproved_rows:
        missing.append(f"{len(unapproved_rows)} owner sign-off rows are not explicitly approved")

    status = "ready_for_phase_1" if not missing else "blocked_owner_input_required"
    result = {
        "status": status,
        "template": str(TEMPLATE.relative_to(ROOT)),
        "required_fields_remaining": len(required),
        "selected_pilot_models": models,
        "owner_signoff_rows_remaining": len(unapproved_rows) if approval_rows else len(approval_roles),
        "blocking_reasons": missing,
        "phase_1_status": "eligible" if not missing else "blocked",
        "production_approval": "not_approved",
    }
    print(json.dumps(result, indent=2))
    return 0 if not args.require_approved or not missing else 1


if __name__ == "__main__":
    raise SystemExit(main())
