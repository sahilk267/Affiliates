#!/usr/bin/env python3
"""Bounded staging smoke and latency harness for ZenithSoles Affiliates.

The default mode performs read-only health checks. Mutation checks require
--allow-mutations plus explicit fixture identifiers and partner credentials.
This tool is intentionally conservative and is not a production load tester.
"""

from __future__ import annotations

import argparse
import concurrent.futures
import hashlib
import hmac
import json
import os
import statistics
import sys
import time
from dataclasses import dataclass
from typing import Any

import requests


@dataclass
class Result:
    status: int
    elapsed_ms: float
    error: str | None = None


def request_once(session: requests.Session, url: str, timeout: float) -> Result:
    started = time.perf_counter()
    try:
        response = session.get(url, timeout=timeout)
        elapsed_ms = (time.perf_counter() - started) * 1000
        if response.status_code >= 400:
            return Result(response.status_code, elapsed_ms, response.text[:200])
        return Result(response.status_code, elapsed_ms)
    except requests.RequestException as exc:
        return Result(0, (time.perf_counter() - started) * 1000, str(exc))


def signed_headers(body: bytes, key: str, secret: str) -> dict[str, str]:
    timestamp = str(int(time.time()))
    signature = hmac.new(
        secret.encode("utf-8"),
        timestamp.encode("ascii") + b"." + body,
        hashlib.sha256,
    ).hexdigest()
    return {
        "Content-Type": "application/json",
        "X-Affiliate-Key": key,
        "X-Affiliate-Timestamp": timestamp,
        "X-Affiliate-Signature": signature,
        "X-Request-ID": f"staging-smoke-{timestamp}",
    }


def run_health_load(base_url: str, count: int, concurrency: int, timeout: float) -> list[Result]:
    url = base_url.rstrip("/") + "/health"

    def worker(_: int) -> Result:
        with requests.Session() as session:
            return request_once(session, url, timeout)

    with concurrent.futures.ThreadPoolExecutor(max_workers=concurrency) as executor:
        return list(executor.map(worker, range(count)))


def print_results(results: list[Result]) -> bool:
    successful = [result for result in results if result.status == 200 and not result.error]
    latencies = [result.elapsed_ms for result in results]
    if not latencies:
        print("No requests completed", file=sys.stderr)
        return False

    print(json.dumps({
        "requests": len(results),
        "successful": len(successful),
        "failed": len(results) - len(successful),
        "min_ms": round(min(latencies), 2),
        "median_ms": round(statistics.median(latencies), 2),
        "p95_ms": round(sorted(latencies)[max(0, int(len(latencies) * 0.95) - 1)], 2),
        "max_ms": round(max(latencies), 2),
    }, indent=2))

    for result in results:
        if result.error:
            print(f"failure status={result.status}: {result.error}", file=sys.stderr)
    return len(successful) == len(results)


def run_optional_mutation_checks(args: argparse.Namespace) -> bool:
    if not args.allow_mutations:
        print("Mutation checks skipped; pass --allow-mutations explicitly in an approved staging run.")
        return True

    key = os.getenv("AFFILIATE_API_KEY")
    secret = os.getenv("AFFILIATE_API_SECRET")
    if not key or not secret:
        print("Mutation checks require AFFILIATE_API_KEY and AFFILIATE_API_SECRET", file=sys.stderr)
        return False
    if not args.click_id or not args.partner_event_id:
        print("Mutation checks require --click-id and --partner-event-id", file=sys.stderr)
        return False

    payload: dict[str, Any] = {
        "click_id": args.click_id,
        "partner_event_id": args.partner_event_id,
        "event_type": "other",
        "conversion_value": 0,
        "currency": "INR",
        "event_data": {"source": "approved-staging-smoke"},
    }
    body = json.dumps(payload, separators=(",", ":")).encode("utf-8")
    url = args.base_url.rstrip("/") + "/api/affiliate/conversion"
    response = requests.post(
        url,
        data=body,
        headers={**signed_headers(body, key, secret), "Idempotency-Key": args.partner_event_id},
        timeout=args.timeout,
    )
    print(json.dumps({
        "conversion_status": response.status_code,
        "conversion_response": response.json() if response.headers.get("content-type", "").startswith("application/json") else response.text[:500],
    }, indent=2))
    return response.status_code in {200, 409}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-url", required=True, help="Staging base URL, for example https://staging.example.test")
    parser.add_argument("--requests", type=int, default=10, help="Bounded health requests; maximum 200 (default: 10)")
    parser.add_argument("--concurrency", type=int, default=2, help="Concurrent workers; maximum 10 (default: 2)")
    parser.add_argument("--timeout", type=float, default=5.0, help="Per-request timeout in seconds")
    parser.add_argument("--allow-mutations", action="store_true", help="Explicitly enable one approved staging conversion check")
    parser.add_argument("--click-id", type=int, help="Staging click fixture for the optional conversion check")
    parser.add_argument("--partner-event-id", help="Unique staging idempotency/event ID for the optional conversion check")
    args = parser.parse_args()
    if not 1 <= args.requests <= 200:
        parser.error("--requests must be between 1 and 200")
    if not 1 <= args.concurrency <= 10:
        parser.error("--concurrency must be between 1 and 10")
    if args.timeout <= 0 or args.timeout > 60:
        parser.error("--timeout must be greater than 0 and no more than 60 seconds")
    return args


def main() -> int:
    args = parse_args()
    base_url = args.base_url.rstrip("/")

    health_response = requests.get(base_url + "/health", timeout=args.timeout)
    print(json.dumps({
        "health_status": health_response.status_code,
        "health_response": health_response.json() if health_response.headers.get("content-type", "").startswith("application/json") else health_response.text[:500],
    }, indent=2))
    if health_response.status_code != 200:
        return 1

    results = run_health_load(base_url, args.requests, args.concurrency, args.timeout)
    load_ok = print_results(results)
    mutation_ok = run_optional_mutation_checks(args)
    return 0 if load_ok and mutation_ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
