# Instructor Revenue Ledger

Laravel implementation of the Career 180 Instructor Revenue Ledger challenge.

Students prepay LMS subscriptions. The platform cut is removed, subscription revenue is allocated among involved instructors, earnings are recognized over time, and instructors are paid through an unreliable mocked provider. The implementation focuses on integer-money correctness, database integrity, idempotent payout generation, safe queued payout execution, and conservative recovery from ambiguous provider outcomes.

## Requirements

- PHP 8.2+ (verified with PHP 8.3)
- Composer
- MySQL 8 compatible database
- Node.js / npm, only if building frontend assets
- Laravel 11, Filament 3, Livewire 3

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` with a MySQL database, then run:

```bash
php artisan migrate
php artisan db:seed
```

For frontend assets:

```bash
npm install
npm run build
```

Run the app:

```bash
php artisan serve
```

The demo Filament panel is available under `/admin`; the instructor ledger is under `/admin/instructors`. It contains a read-only instructor balance screen and payout history.

## Testing

Tests use PHPUnit. `phpunit.xml` points the suite at an isolated MySQL database named `instructor_revenue_ledger_test`; create that database before running tests.

```bash
php artisan test
```

## Useful Commands

```bash
php artisan earnings:recognize
php artisan payouts:run 2026-09
php artisan payouts:sweep-stuck
```

- `earnings:recognize`: recognizes due earning schedule rows.
- `payouts:run`: creates payout batches and dispatches payout execution jobs.
- `payouts:sweep-stuck`: moves stale `processing` payouts to `uncertain` and queues reconciliation.

## Assumptions

- Money is stored as integer minor units.
- Subscription fees are prepaid.
- The platform cut is stored on the subscription payment and removed before instructor allocation.
- Instructor revenue is split among participating instructors using deterministic ordering.
- Uneven splits allocate the remainder one minor unit at a time to earlier sorted recipients.
- Allocation decides who should eventually receive money; recognition decides when it becomes payable.
- Recognized, unvoided, unclaimed earnings are payout-eligible.
- Refunds preserve already recognized or claimed earnings and void only future unearned schedule rows.
- Ambiguous provider outcomes become `uncertain` and are reconciled with `checkStatus()` rather than repaid.

More detail is in `docs/ARCHITECTURE.md`.
