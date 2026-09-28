# Architecture

## Domain Flow

The money lifecycle is:

`subscription payment -> revenue allocation -> earning schedule -> recognition -> payout batch -> payout items -> provider execution -> reconciliation -> paid state`

`SubscriptionPayment` records prepaid subscription money in integer minor units. `AllocatePaymentToInstructors` subtracts the stored platform cut, splits the instructor pool, creates `SubscriptionPaymentAllocation` rows, and spreads each allocation across `EarningSchedule` rows. `RecognizeEarnings` marks due, unvoided schedule rows as recognized. `GeneratePayoutBatch` claims recognized, unvoided, unpaid schedule rows into `Payout` and `PayoutItem` records. `ExecutePayout` calls `MockPaymentProvider::pay()` only after atomically claiming a payout. Ambiguous payouts are handled by `ReconcilePayout`, which uses `checkStatus()` only.

Refunds enter after subscription/payment creation. `ProcessRefund` creates an idempotent `SubscriptionRefund` and voids only future, unrecognized, unclaimed earning schedule rows.

## Revenue Allocation

Money is stored as integer minor units. Floating point arithmetic is avoided because fractional binary representation is unsafe for financial totals.

The subscription payment stores:

- `amount_minor`
- `platform_cut_minor`
- `currency`

The instructor pool is:

`amount_minor - platform_cut_minor`

Participating instructors are selected from `subscription_enrollments` for the payment. Instructor IDs are sorted ascending before splitting, which makes remainder assignment deterministic. `Money::allocate()` uses integer division, then gives one extra minor unit to the earliest shares until the remainder is exhausted. This preserves the exact total.

Each instructor allocation is then split across the subscription term using the same exact allocation helper. This creates monthly `earning_schedules` for recognition.

## Allocation vs Recognition

Allocation answers: who should eventually receive the money?

Recognition answers: when does that allocated money become earned and payable?

Keeping these separate lets the system allocate prepaid subscription revenue immediately while recognizing earnings over time. `RecognizeEarnings` promotes schedule rows whose `earn_date` is due, provided they are not already recognized and not voided.

The operational command is:

```bash
php artisan earnings:recognize
```

It is scheduled daily in `routes/console.php`.

## Instructor Balances

The Filament screen derives balances from ledger/schedule/payout records. It does not rely on a mutable balance column.

Definitions used by `InstructorBalanceSummary`:

- Recognized: `earning_schedules` that are recognized and not voided.
- Paid: recognized, non-voided earnings attached to `payout_items` whose payout status is `succeeded`.
- Available: recognized, non-voided earnings with no payout item.
- Reserved / in payout: recognized, non-voided earnings attached to a non-succeeded payout (`pending`, `processing`, `failed`, or `uncertain`).
- Outstanding: recognized minus paid. This equals available plus reserved under the current payout-item retention design.

The current UI uses aggregate queries at read time. That is simple and correct for the challenge. At larger scale, a read-model or snapshot table could be maintained asynchronously for faster dashboard reads.

## Payout Idempotency

Duplicate payments are prevented in layers.

### Payout Generation

`payouts` has a unique constraint on:

`(instructor_id, period_key, currency)`

If concurrent generation attempts race, the database constraint is the final guard. The service treats a duplicate as idempotent only when the logical payout already exists.

### Payout Items

`payout_items.earning_schedule_id` is unique. A schedule row can be claimed by only one payout item.

### Provider Idempotency Key

Each payout has a unique `provider_idempotency_key`. The mocked provider records attempts by this key, which lets tests prove provider-level side effects and lets reconciliation ask for provider status.

### Atomic Worker Claim

`ExecutePayout` performs a guarded atomic update from `pending` or `failed` to `processing`. Only the worker whose update affects the row may call the provider. That database operation finishes before `pay()` is called, so no lock or transaction is held across the provider boundary.

## Technical Retry vs Financial Retry

A technical retry is not automatically a financial retry.

Queued jobs may run more than once. That does not mean the system can safely call an external payment provider more than once. Once a provider call may have moved money, the system must determine the provider result before any further financial action.

This is why `uncertain` exists. It represents ambiguity, not failure.

## Payout State Machine

Actual payout states:

- `pending`
- `processing`
- `succeeded`
- `failed`
- `uncertain`

Transitions:

- `pending -> processing`
- `failed -> processing`
- `processing -> succeeded` after confirmed provider success
- `processing -> failed` after confirmed provider failure
- `processing -> uncertain` after provider timeout
- `uncertain -> succeeded` after reconciliation confirms success
- `uncertain -> failed` after reconciliation confirms failure
- repeated unknown reconciliation leaves status `uncertain` and sets `requires_manual_review`
- stale `processing -> uncertain` through `payouts:sweep-stuck`

`failed` means the provider confirmed that money did not move, so a later execution attempt can be safe. `uncertain` means money may already have moved, so `pay()` must not be called again until status is resolved.

## Provider Timeout Handling

Timeout-after-success is handled conservatively:

1. The application calls `pay()`.
2. The provider may transfer money.
3. The application receives an ambiguous timeout.
4. The payout becomes `uncertain`.
5. No additional `pay()` call is made.
6. `ReconcilePayout` calls `checkStatus()`.
7. Provider status resolves the payout.

`ReconcilePayout` never calls `pay()`. This is intentional.

## Worker Crash Handling

Crash before claim: no provider call occurred, so another worker can later claim the payout.

Crash after claim but before provider call: the payout may remain `processing`. The stuck sweep moves stale `processing` payouts to `uncertain`, because the system cannot always prove whether the provider was called.

Crash during or after provider call: the provider may have moved money even if the application did not persist the result. The payout must not be reset to `pending` or `failed`. It moves through the uncertain/reconciliation path.

## Refunds

`ProcessRefund` uses an idempotency key and voids earning schedule rows only when all are true:

- unrecognized
- unvoided
- unpaid/unclaimed
- `earn_date` is strictly after the current month

Recognized earnings remain untouched. Already claimed or paid earnings remain untouched.

The business rule is deliberately simple: already recognized/current earned revenue remains earned, while future unearned revenue is prevented from becoming payable after refund/cancellation. Proportional refund arithmetic is not implemented.

## Scaling

The current design supports scale through database-enforced integrity and short critical sections:

- monetary rows use integer columns
- payout identity and payout item uniqueness are enforced in MySQL
- recognition, payout, and balance queries filter in SQL
- provider calls happen outside DB transactions
- queue jobs are idempotent at the application and database layer
- processing can be distributed across workers without relying on in-memory locks

Current implementation is sufficient for the challenge, but a production system with hundreds of thousands of active subscriptions and tens of millions of schedule rows would likely add:

- chunked recognition sweeps
- chunked payout generation by instructor/currency
- partitioning or archival for large schedule/ledger tables
- pre-aggregated instructor balance read models
- separate reconciliation queues
- queue partitioning by payout or instructor key
- provider rate limiting
- operational dashboards for manual review
- metrics and alerts for stuck/uncertain payouts

## Known Limitations

- The payment provider is mocked.
- Refunds are intentionally simplified and do not prorate partial refunds.
- The Filament UI is read-only and demo-oriented; it is not a production access-control implementation.
- Manual review is represented by a flag, not a full operations workflow.
- Balances are derived from database aggregates at read time.
- There is no foreign exchange or currency conversion.
- Observability and alerting are outside the challenge scope.
