#!/usr/bin/env python3
"""Run non-destructive partner API contract checks against staging.

By default this verifies health and expected rejection behavior for malformed,
expired, and invalidly signed requests. Valid financial mutations require
--allow-mutations plus explicit staging fixtures and credentials.
"""

from __future__ import annotations

import argparse
import hashlib
import hmac
import json
import os
import time
import uuid
from typing import Any

import requests


def sign(body: bytes, key: str, secret: str, timestamp: int | None = None) -> dict[str, str]:
    timestamp = timestamp or int(time.time())
    digest = hmac.new(secret.encode(), str(timestamp).encode() + b"." + body, hashlib.sha256).hexdigest()
    return {
        "Content-Type": "application/json",
        "X-Affiliate-Key": key,
        "X-Affiliate-Timestamp": str(timestamp),
        "X-Affiliate-Signature": digest,
        "X-Request-ID": f"partner-contract-{uuid.uuid4()}",
    }


def expect(name: str, response: requests.Response, allowed: set[int]) -> dict[str, Any]:
    passed = response.status_code in allowed
    return {
        "name": name,
        "status": response.status_code,
        "expected": sorted(allowed),
        "passed": passed,
        "body": response.text[:500],
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-url", required=True)
    parser.add_argument("--timeout", type=float, default=5.0)
    parser.add_argument("--allow-mutations", action="store_true")
    parser.add_argument("--click-id", type=int)
    parser.add_argument("--user-id", type=int)
    parser.add_argument("--partner-event-id")
    args = parser.parse_args()

    base = args.base_url.rstrip("/")
    results: list[dict[str, Any]] = []
    health = requests.get(base + "/health", timeout=args.timeout)
    results.append(expect("health", health, {200}))

    key = os.getenv("AFFILIATE_API_KEY", "staging-placeholder-key")
    secret = os.getenv("AFFILIATE_API_SECRET", "staging-placeholder-secret")
    body = json.dumps({
        "click_id": args.click_id or 0,
        "partner_event_id": "contract-negative-check",
        "event_type": "other",
    }, separators=(",", ":")).encode()

    invalid_signature_headers = sign(body, key, secret)
    invalid_signature_headers["X-Affiliate-Signature"] = "0" * 64
    invalid = requests.post(base + "/api/affiliate/conversion", data=body, headers=invalid_signature_headers, timeout=args.timeout)
    results.append(expect("invalid_signature", invalid, {401}))

    expired_headers = sign(body, key, secret, int(time.time()) - 3600)
    expired = requests.post(base + "/api/affiliate/conversion", data=body, headers=expired_headers, timeout=args.timeout)
    results.append(expect("expired_timestamp", expired, {401}))

    malformed_body = b"{\"event_type\":\"other\"}"
    malformed_headers = sign(malformed_body, key, secret)
    malformed = requests.post(base + "/api/affiliate/conversion", data=malformed_body, headers=malformed_headers, timeout=args.timeout)
    results.append(expect("signed_validation_failure", malformed, {422, 503}))

    if args.allow_mutations:
        if not os.getenv("AFFILIATE_API_KEY") or not os.getenv("AFFILIATE_API_SECRET"):
            raise SystemExit("--allow-mutations requires AFFILIATE_API_KEY and AFFILIATE_API_SECRET")
        if not args.click_id or not args.partner_event_id:
            raise SystemExit("--allow-mutations requires --click-id and --partner-event-id")
        event_body = json.dumps({
            "click_id": args.click_id,
            "partner_event_id": args.partner_event_id,
            "event_type": "other",
            "conversion_value": 0,
            "currency": "INR",
            "event_data": {"source": "approved-staging-contract-check"},
        }, separators=(",", ":")).encode()
        event_headers = sign(event_body, os.environ["AFFILIATE_API_KEY"], os.environ["AFFILIATE_API_SECRET"])
        event_headers["Idempotency-Key"] = args.partner_event_id
        first = requests.post(base + "/api/affiliate/conversion", data=event_body, headers=event_headers, timeout=args.timeout)
        replay = requests.post(base + "/api/affiliate/conversion", data=event_body, headers=event_headers, timeout=args.timeout)
        results.append(expect("valid_conversion_or_conflict", first, {200, 409}))
        results.append(expect("idempotent_replay_or_conflict", replay, {200, 409}))
    else:
        results.append({"name": "valid_mutation_checks", "passed": True, "skipped": True})

    print(json.dumps({"base_url": base, "results": results, "passed": all(item["passed"] for item in results)}, indent=2))
    return 0 if all(item["passed"] for item in results) else 1


if __name__ == "__main__":
    raise SystemExit(main())
