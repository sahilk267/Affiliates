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
APPROVAL_ROLES = (
    "Product owner",
    "Release owner",
    "Security owner",
    "Finance/payout owner",
)


def required_lines(text: str) -> list[str]:
    return [line.strip() for line in text.splitlines() if REQUIRED_MARKER in line]


def checked_models(text: str) -> list[str]:
    """Return selected model labels from plain or backtick-wrapped Markdown checkboxes."""
    matches = re.findall(r"-\s+`?\[([ xX])\]`?\s*(.+)", text)
    return [
        label.strip()
        for mark, label in matches
        if mark.lower() == "x" and label.strip() in MODEL_OPTIONS.values()
    ]


def approval_rows(text: str) -> list[str]:
    """Read only the owner sign-off rows, never similarly named fields above them."""
    signoff_section = text.split("## 8. Owner sign-off", 1)[-1]
    signoff_table = signoff_section.split("## Current status", 1)[0]
    return [
        line.strip()
        for line in signoff_table.splitlines()
        if line.startswith("|")
        and any(line.startswith(f"| {role} |") for role in APPROVAL_ROLES)
    ]


def unapproved_rows(rows: list[str]) -> list[str]:
    unapproved: list[str] = []
    for row in rows:
        fields = [field.strip() for field in row.strip().strip("|").split("|")]
        if len(fields) < 5 or fields[2].lower() != "approve":
            unapproved.append(row)
    return unapproved


def validate_text(text: str) -> dict[str, object]:
    """Return deterministic validation facts for a pilot decision document."""
    required = required_lines(text)
    models = checked_models(text)
    rows = approval_rows(text)
    unapproved = unapproved_rows(rows)
    blocking_reasons: list[str] = []

    if required:
        blocking_reasons.append(f"{len(required)} required fields still contain {REQUIRED_MARKER}")
    if len(models) != 1:
        blocking_reasons.append(
            f"exactly one approved pilot model must be selected; checked={len(models)}"
        )
    if len(rows) != len(APPROVAL_ROLES):
        blocking_reasons.append(
            f"all {len(APPROVAL_ROLES)} owner sign-off rows are required; found={len(rows)}"
        )
    elif unapproved:
        blocking_reasons.append(
            f"{len(unapproved)} owner sign-off rows are not explicitly approved"
        )

    return {
        "status": "ready_for_phase_1" if not blocking_reasons else "blocked_owner_input_required",
        "required_fields_remaining": len(required),
        "selected_pilot_models": models,
        "owner_signoff_rows_remaining": len(unapproved) if rows else len(APPROVAL_ROLES),
        "blocking_reasons": blocking_reasons,
        "phase_1_status": "eligible" if not blocking_reasons else "blocked",
        "production_approval": "not_approved",
    }


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

    result = validate_text(TEMPLATE.read_text(encoding="utf-8"))
    result["template"] = str(TEMPLATE.relative_to(ROOT))
    print(json.dumps(result, indent=2))
    return 0 if not args.require_approved or result["blocking_reasons"] == [] else 1


if __name__ == "__main__":
    raise SystemExit(main())
