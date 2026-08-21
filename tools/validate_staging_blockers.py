#!/usr/bin/env python3
"""Validate the staging blocker register without requiring credentials or live systems."""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REGISTER = ROOT / "STAGING_BLOCKER_REGISTER.md"
ROW_RE = re.compile(r"^\|\s*(STG-\d{3})\s*\|")


def parse_rows() -> list[dict[str, str]]:
    rows: list[dict[str, str]] = []
    for line in REGISTER.read_text(encoding="utf-8").splitlines():
        if not ROW_RE.match(line):
            continue
        fields = [field.strip() for field in line.strip().strip("|").split("|")]
        if len(fields) != 7:
            raise ValueError(f"Malformed blocker row with {len(fields)} fields: {line}")
        blocker_id, control, owner, due, evidence, stop_condition, status = fields
        rows.append({
            "id": blocker_id,
            "control": control,
            "owner": owner,
            "due_utc": due,
            "required_evidence": evidence,
            "stop_condition": stop_condition,
            "status": status,
        })
    return rows


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--require-ready",
        action="store_true",
        help="Return non-zero unless every blocker has a named owner, due date, evidence, cleared stop condition, and Closed status.",
    )
    args = parser.parse_args()

    if not REGISTER.is_file():
        print(json.dumps({"status": "error", "error": "blocker register missing"}, indent=2))
        return 2

    try:
        rows = parse_rows()
    except ValueError as exc:
        print(json.dumps({"status": "error", "error": str(exc)}, indent=2))
        return 2

    incomplete: list[dict[str, str]] = []
    for row in rows:
        owner_is_role_only = row["owner"].lower().endswith("owner") or "legal owner" in row["owner"].lower()
        status_is_closed = "closed" in row["status"].lower()
        required_fields_present = all(row[key] for key in ("owner", "due_utc", "required_evidence", "stop_condition"))
        if not required_fields_present or owner_is_role_only or not status_is_closed:
            incomplete.append({
                "id": row["id"],
                "owner_assigned": bool(row["owner"]) and not owner_is_role_only,
                "due_assigned": bool(row["due_utc"]),
                "evidence_recorded": status_is_closed,
                "status": row["status"],
            })

    result = {
        "status": "ready" if not incomplete else "open_blockers",
        "register": str(REGISTER.relative_to(ROOT)),
        "total_blockers": len(rows),
        "closed_blockers": len(rows) - len(incomplete),
        "open_or_incomplete_blockers": len(incomplete),
        "blockers": incomplete,
        "production_approval": "eligible" if not incomplete else "blocked",
    }
    print(json.dumps(result, indent=2))
    return 0 if not args.require_ready or not incomplete else 1


if __name__ == "__main__":
    raise SystemExit(main())
