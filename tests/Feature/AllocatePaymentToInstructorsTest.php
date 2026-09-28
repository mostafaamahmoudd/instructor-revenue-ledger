<?php

namespace Tests\Feature;

use App\Exceptions\NoEligibleInstructorsException;
use App\Models\Course;
use App\Models\EarningSchedule;
use App\Models\Instructor;
use App\Models\Subscription;
use App\Models\SubscriptionEnrollment;
use App\Models\SubscriptionPayment;
use App\Services\AllocatePaymentToInstructors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocatePaymentToInstructorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocation_totals_plus_platform_cut_equal_payment_amount(): void
    {
        foreach ([1, 2, 3, 7] as $instructorCount) {
            $payment = $this->createPaymentWithInstructors('monthly', $instructorCount, 10000, 333);

            app(AllocatePaymentToInstructors::class)->run($payment);

            $this->assertSame(
                $payment->amount_minor,
                (int) $payment->allocations()->sum('amount_minor') + $payment->platform_cut_minor,
                "Failed exact conservation for {$instructorCount} instructors."
            );
        }
    }

    public function test_remainder_assignment_uses_instructor_id_ascending_tie_break(): void
    {
        $payment = $this->createPaymentWithInstructors('monthly', 0, 10000, 333);
        $instructors = Instructor::factory()->count(3)->create()->sortBy('id')->values();

        foreach ([2, 0, 1] as $index) {
            $course = Course::factory()->create(['instructor_id' => $instructors[$index]->id]);
            SubscriptionEnrollment::factory()
                ->for($payment, 'payment')
                ->for($instructors[$index])
                ->for($course)
                ->create();
        }

        app(AllocatePaymentToInstructors::class)->run($payment);

        $this->assertSame(
            [
                ['instructor_id' => $instructors[0]->id, 'amount_minor' => 3223],
                ['instructor_id' => $instructors[1]->id, 'amount_minor' => 3222],
                ['instructor_id' => $instructors[2]->id, 'amount_minor' => 3222],
            ],
            $payment->allocations()
                ->orderBy('instructor_id')
                ->get(['instructor_id', 'amount_minor'])
                ->map(fn ($allocation) => $allocation->only(['instructor_id', 'amount_minor']))
                ->all()
        );
    }

    public function test_running_twice_does_not_duplicate_allocations_or_schedules(): void
    {
        $payment = $this->createPaymentWithInstructors('quarterly', 3, 10000, 333);
        $service = app(AllocatePaymentToInstructors::class);

        $service->run($payment);
        $firstCounts = $this->countsForPayment($payment);

        $service->run($payment->fresh());
        $secondCounts = $this->countsForPayment($payment);

        $this->assertSame($firstCounts, $secondCounts);
    }

    public function test_zero_enrolled_instructors_throws(): void
    {
        $payment = $this->createPaymentWithInstructors('monthly', 0, 5000, 1000);

        $this->expectException(NoEligibleInstructorsException::class);

        app(AllocatePaymentToInstructors::class)->run($payment);
    }

    public function test_schedule_row_count_per_allocation_matches_plan_term_length(): void
    {
        foreach (['monthly' => 1, 'quarterly' => 3, 'annual' => 12] as $plan => $expectedRows) {
            $payment = $this->createPaymentWithInstructors($plan, 2, 10000, 333);

            app(AllocatePaymentToInstructors::class)->run($payment);

            foreach ($payment->allocations as $allocation) {
                $this->assertSame(
                    $expectedRows,
                    EarningSchedule::query()->where('allocation_id', $allocation->id)->count()
                );
            }
        }
    }

    public function test_schedule_rows_sum_to_allocation_amount_for_quarterly_and_annual_plans(): void
    {
        foreach (['quarterly', 'annual'] as $plan) {
            $payment = $this->createPaymentWithInstructors($plan, 3, 10001, 333);

            app(AllocatePaymentToInstructors::class)->run($payment);

            foreach ($payment->allocations as $allocation) {
                $this->assertSame(
                    $allocation->amount_minor,
                    (int) EarningSchedule::query()
                        ->where('allocation_id', $allocation->id)
                        ->sum('amount_minor')
                );
            }
        }
    }

    private function createPaymentWithInstructors(
        string $plan,
        int $instructorCount,
        int $amountMinor,
        int $platformCutMinor
    ): SubscriptionPayment {
        $subscription = Subscription::factory()->create([
            'plan' => $plan,
            'amount_minor' => $amountMinor,
            'currency' => 'USD',
            'start_date' => '2027-01-31',
            'end_date' => match ($plan) {
                'monthly' => '2027-02-28',
                'quarterly' => '2027-04-30',
                'annual' => '2028-01-31',
            },
        ]);

        $payment = SubscriptionPayment::factory()->for($subscription)->create([
            'amount_minor' => $amountMinor,
            'platform_cut_minor' => $platformCutMinor,
            'currency' => 'USD',
        ]);

        for ($i = 0; $i < $instructorCount; $i++) {
            $instructor = Instructor::factory()->create();
            $course = Course::factory()->create(['instructor_id' => $instructor->id]);
            SubscriptionEnrollment::factory()
                ->for($payment, 'payment')
                ->for($instructor)
                ->for($course)
                ->create();
        }

        return $payment;
    }

    private function countsForPayment(SubscriptionPayment $payment): array
    {
        $allocationIds = $payment->allocations()->pluck('id');

        return [
            'allocations' => $allocationIds->count(),
            'schedules' => EarningSchedule::query()->whereIn('allocation_id', $allocationIds)->count(),
        ];
    }
}
