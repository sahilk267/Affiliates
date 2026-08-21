#!/usr/bin/env python3
"""Compare platform and provider payout exports without changing either file.

Expected CSV columns in both files:
  platform_id, user_id, amount_or_points, status, external_reference

The platform export may include additional fields such as batch_id and
platform_type. Monetary/points values are compared as Decimal values.
"""

from __future__ import annotations

import argparse
import csv
import json
import sys
from collections import Counter
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Any

REQUIRED = {"platform_id", "user_id", "amount_or_points", "status", "external_reference"}


def load_rows(path: Path) -> tuple[list[dict[str, str]], list[str]]:
    with path.open(newline="", encoding="utf-8-sig") as handle:
        reader = csv.DictReader(handle)
        fields = set(reader.fieldnames or [])
        missing = sorted(REQUIRED - fields)
        rows = [{key: (value or "").strip() for key, value in row.items()} for row in reader]
    return rows, missing


def amount(value: str) -> Decimal | None:
    try:
        return Decimal(value)
    except (InvalidOperation, TypeError):
        return None


def duplicate_ids(rows: list[dict[str, str]]) -> list[str]:
    return sorted(key for key, count in Counter(row.get("platform_id", "") for row in rows).items() if key and count > 1)


def reconcile(platform_rows: list[dict[str, str]], provider_rows: list[dict[str, str]]) -> dict[str, Any]:
    platform = {row["platform_id"]: row for row in platform_rows if row.get("platform_id")}
    provider = {row["platform_id"]: row for row in provider_rows if row.get("platform_id")}
    missing_provider = sorted(set(platform) - set(provider))
    unexpected_provider = sorted(set(provider) - set(platform))
    amount_mismatches: list[dict[str, str]] = []
    status_mismatches: list[dict[str, str]] = []
    reference_mismatches: list[dict[str, str]] = []

    for platform_id in sorted(set(platform) & set(provider)):
        left = platform[platform_id]
        right = provider[platform_id]
        left_amount = amount(left["amount_or_points"])
        right_amount = amount(right["amount_or_points"])
        if left_amount is None or right_amount is None or left_amount != right_amount:
            amount_mismatches.append({
                "platform_id": platform_id,
                "platform_amount": left["amount_or_points"],
                "provider_amount": right["amount_or_points"],
            })
        if left["status"].lower() != right["status"].lower():
            status_mismatches.append({
                "platform_id": platform_id,
                "platform_status": left["status"],
                "provider_status": right["status"],
            })
        if left["external_reference"] and right["external_reference"] and left["external_reference"] != right["external_reference"]:
            reference_mismatches.append({
                "platform_id": platform_id,
                "platform_reference": left["external_reference"],
                "provider_reference": right["external_reference"],
            })

    duplicates = {
        "platform": duplicate_ids(platform_rows),
        "provider": duplicate_ids(provider_rows),
    }
    exceptions = {
        "missing_provider": missing_provider,
        "unexpected_provider": unexpected_provider,
        "amount_mismatches": amount_mismatches,
        "status_mismatches": status_mismatches,
        "reference_mismatches": reference_mismatches,
        "duplicate_platform_ids": duplicates["platform"],
        "duplicate_provider_ids": duplicates["provider"],
    }
    exception_count = sum(len(values) for values in exceptions.values())
    return {
        "summary": {
            "platform_rows": len(platform_rows),
            "provider_rows": len(provider_rows),
            "matched_ids": len(set(platform) & set(provider)),
            "exception_count": exception_count,
            "status": "matched" if exception_count == 0 else "exceptions_found",
        },
        "exceptions": exceptions,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("platform_export", type=Path)
    parser.add_argument("provider_export", type=Path)
    parser.add_argument("--output", type=Path, help="Optional JSON report path")
    args = parser.parse_args()

    platform_rows, platform_missing = load_rows(args.platform_export)
    provider_rows, provider_missing = load_rows(args.provider_export)
    report: dict[str, Any] = {
        "inputs": {
            "platform_export": str(args.platform_export),
            "provider_export": str(args.provider_export),
        },
        "schema_errors": {
            "platform_missing_columns": platform_missing,
            "provider_missing_columns": provider_missing,
        },
    }
    if platform_missing or provider_missing:
        report["summary"] = {"status": "schema_error", "exception_count": 0}
    else:
        report.update(reconcile(platform_rows, provider_rows))

    output = json.dumps(report, indent=2, sort_keys=True)
    if args.output:
        args.output.write_text(output + "\n", encoding="utf-8")
    print(output)
    if platform_missing or provider_missing:
        return 2
    return 0 if report["summary"]["exception_count"] == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
