# ADR-0001: Centralize Financial State Transitions

## Status

Accepted.

## Context

The platform manages points balances, cashback and referral credits, commissions, withdrawals, and gift or cash redemptions. These operations can be retried by partner systems, administrators, or end users and must not create duplicate financial records or illegal state transitions.

## Decision

Financial mutations are coordinated through `PointsService` and `PayoutService`. Wallet changes use row-level locking and deterministic idempotency keys. Conversion processing uses `partner_event_id`; cashback, referral, withdrawal, and refund ledger entries use deterministic keys derived from their owning record. Commission and redemption models reject invalid state transitions, and orchestration occurs inside database transactions.

## Consequences

Controllers remain responsible for request validation and response formatting, while domain services own financial atomicity. External payout providers and partner adapters must preserve the platform identifiers and retry contract. Operational reconciliation must compare platform records with provider references rather than repairing records by deletion.

## Alternatives rejected

Direct controller debit-plus-create sequences were rejected because a failure between the two writes could leave a wallet debited without a redemption request. Blind retries were rejected because they can duplicate rewards or payouts. A compensating ledger transaction is used for approved rejection refunds instead of mutating historical ledger entries.
