# AI Usage

AI-assisted tooling was used during this challenge. It was used as an engineering assistant, not as a substitute for verification.

## How AI Was Used

AI assistance helped with:

- repository reconnaissance
- understanding the existing Laravel codebase quickly
- comparing the implementation against challenge requirements
- identifying possible concurrency and idempotency defects
- targeted implementation assistance
- test generation and review
- documentation drafting
- review checklists

No secrets or local credentials are included in this document.

## Workflow

The work followed a staged, verification-driven process:

1. Inspect the repository without modifying it.
2. Map the implemented architecture and financial flows.
3. Compare code, schema, and tests against the challenge requirements.
4. Identify verified defects and risks.
5. Make targeted changes to the payout engine, refunds, recognition, UI, seeding, and docs.
6. Add tests that prove provider-level side effects, not only final database state.
7. Run focused tests and the full PHPUnit suite.
8. Verify live MySQL constraints and demo seed data.

## Engineering Decisions

AI assistance was constrained by the project architecture and financial correctness requirements. Important decisions reviewed and preserved include:

- store money as integer minor units
- avoid floating point for business calculations
- distribute split remainders deterministically
- separate allocation from recognition
- use database uniqueness as an integrity layer
- atomically claim payouts before provider calls
- keep provider calls outside DB transactions and locks
- distinguish `failed` from `uncertain`
- never repeat `pay()` after an ambiguous timeout
- reconcile uncertain payouts with `checkStatus()`
- move stale `processing` payouts to `uncertain`
- void only future unearned refund rows
- reason from ledger-style records rather than mutable balances

AI influenced analysis and implementation, but changes were accepted based on code review, tests, schema inspection, and consistency with the challenge requirements.

## Generated vs Reviewed

AI-assisted output was not accepted blindly. Verification included:

- source review
- focused PHPUnit tests
- provider-level `attemptsFor()` assertions
- full PHPUnit suite
- live MySQL `SHOW CREATE TABLE` inspection
- demo seeder verification against an isolated database
- Laravel route and Filament boot checks

Tests specifically prove that repeated payout generation, retried jobs, duplicate/stale workers, timeout reconciliation, and stuck-processing recovery do not create duplicate provider payment attempts.

## Constraints Applied

Several suggestions and implementation paths were constrained or rejected:

- no unnecessary interfaces
- no DTO classes
- no Action-class proliferation
- no broad stylistic refactors during financial hardening
- no DB lock or transaction held across provider calls
- no automatic retry of uncertain payments through `pay()`
- no proportional refund redesign without a business requirement
- no UI actions that can execute or retry payouts
- tests must prove provider `pay()` call counts, not merely final payout status

## Technical Differentiators

The important parts of the submission are:

- layered idempotency
- database-enforced financial integrity
- atomic worker claiming
- explicit payout state machine
- conservative handling of ambiguous provider outcomes
- reconciliation through provider status checks
- append-only/ledger-oriented reasoning
- provider-side-effect tests
- simple read-only operational visibility through Filament
