<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\EarningSchedule;
use App\Models\Instructor;
use App\Models\Payout;
use App\Models\PayoutBatch;
use App\Models\PayoutItem;
use App\Models\Student;
use App\Models\StudentCourseAccess;
use App\Models\Subscription;
use App\Models\SubscriptionEnrollment;
use App\Models\SubscriptionPayment;
use App\Services\AllocatePaymentToInstructors;
use App\Services\RecognizeEarnings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = [
            'maya' => Instructor::factory()->create([
                'name' => 'Maya Hassan',
                'email' => 'maya.hassan@example.com',
            ]),
            'omar' => Instructor::factory()->create([
                'name' => 'Omar Nasser',
                'email' => 'omar.nasser@example.com',
            ]),
            'lina' => Instructor::factory()->create([
                'name' => 'Lina Farouk',
                'email' => 'lina.farouk@example.com',
            ]),
        ];

        $courses = [
            'laravel' => Course::factory()->create([
                'instructor_id' => $instructors['maya']->id,
                'title' => 'Laravel Revenue Systems',
            ]),
            'mysql' => Course::factory()->create([
                'instructor_id' => $instructors['omar']->id,
                'title' => 'MySQL for Financial Workloads',
            ]),
            'testing' => Course::factory()->create([
                'instructor_id' => $instructors['lina']->id,
                'title' => 'Testing Payment Workflows',
            ]),
        ];

        $students = [
            Student::factory()->create([
                'name' => 'Salma Ibrahim',
                'email' => 'salma.ibrahim@example.com',
            ]),
            Student::factory()->create([
                'name' => 'Youssef Adel',
                'email' => 'youssef.adel@example.com',
            ]),
        ];

        $this->createPaidSubscription(
            student: $students[0],
            plan: 'quarterly',
            amountMinor: 12000,
            platformCutMinor: 2400,
            startDate: '2026-01-01',
            endDate: '2026-04-01',
            courses: [$courses['laravel'], $courses['mysql']],
            idempotencyKey: 'demo-payment-quarterly'
        );

        $this->createPaidSubscription(
            student: $students[1],
            plan: 'annual',
            amountMinor: 24000,
            platformCutMinor: 4800,
            startDate: '2026-01-01',
            endDate: '2027-01-01',
            courses: [$courses['mysql'], $courses['testing']],
            idempotencyKey: 'demo-payment-annual'
        );

        app(RecognizeEarnings::class)->run(CarbonImmutable::parse('2027-01-01'));

        $this->markInstructorPaid($instructors['maya'], '2026-04');
        $this->reserveSomeEarnings($instructors['omar'], '2026-05');
    }

    private function createPaidSubscription(
        Student $student,
        string $plan,
        int $amountMinor,
        int $platformCutMinor,
        string $startDate,
        string $endDate,
        array $courses,
        string $idempotencyKey
    ): void {
        $subscription = Subscription::factory()->create([
            'student_id' => $student->id,
            'plan' => $plan,
            'amount_minor' => $amountMinor,
            'currency' => 'USD',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $payment = SubscriptionPayment::factory()->create([
            'subscription_id' => $subscription->id,
            'amount_minor' => $amountMinor,
            'platform_cut_minor' => $platformCutMinor,
            'currency' => 'USD',
            'idempotency_key' => $idempotencyKey,
            'provider_reference' => 'demo_' . $idempotencyKey,
            'paid_at' => CarbonImmutable::parse($startDate)->addDay(),
            'created_at' => CarbonImmutable::parse($startDate)->addDay(),
        ]);

        foreach ($courses as $course) {
            StudentCourseAccess::query()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'created_at' => CarbonImmutable::parse($startDate)->addDay(),
            ]);

            SubscriptionEnrollment::query()->create([
                'subscription_payment_id' => $payment->id,
                'instructor_id' => $course->instructor_id,
                'course_id' => $course->id,
            ]);
        }

        app(AllocatePaymentToInstructors::class)->run($payment);
    }

    private function markInstructorPaid(Instructor $instructor, string $periodKey): void
    {
        $earnings = EarningSchedule::query()
            ->where('instructor_id', $instructor->id)
            ->recognized()
            ->notVoided()
            ->unpaid()
            ->orderBy('earn_date')
            ->get();

        $amountMinor = (int)$earnings->sum('amount_minor');

        $batch = PayoutBatch::create([
            'period_key' => $periodKey,
            'triggered_at' => CarbonImmutable::parse($periodKey . '-28'),
            'status' => PayoutBatch::STATUS_COMPLETED,
            'created_at' => CarbonImmutable::parse($periodKey . '-28'),
        ]);

        $payout = Payout::create([
            'payout_batch_id' => $batch->id,
            'instructor_id' => $instructor->id,
            'instructor_type' => 'instructor',
            'period_key' => $periodKey,
            'amount_minor' => $amountMinor,
            'currency' => 'USD',
            'status' => Payout::STATUS_SUCCEEDED,
            'provider_idempotency_key' => (string)Str::uuid(),
            'provider_reference' => 'demo_paid_' . $instructor->id,
            'attempts' => 1,
            'requires_manual_review' => false,
            'last_checked_at' => CarbonImmutable::parse($periodKey . '-28'),
            'created_at' => CarbonImmutable::parse($periodKey . '-28'),
            'updated_at' => CarbonImmutable::parse($periodKey . '-28'),
        ]);

        foreach ($earnings as $earning) {
            PayoutItem::create([
                'payout_id' => $payout->id,
                'earning_schedule_id' => $earning->id,
                'amount_minor' => $earning->amount_minor,
            ]);
        }
    }

    private function reserveSomeEarnings(Instructor $instructor, string $periodKey): void
    {
        $earnings = EarningSchedule::query()
            ->where('instructor_id', $instructor->id)
            ->recognized()
            ->notVoided()
            ->unpaid()
            ->orderBy('earn_date')
            ->limit(4)
            ->get();

        $amountMinor = (int)$earnings->sum('amount_minor');

        $batch = PayoutBatch::create([
            'period_key' => $periodKey,
            'triggered_at' => CarbonImmutable::parse($periodKey . '-28'),
            'status' => PayoutBatch::STATUS_COMPLETED,
            'created_at' => CarbonImmutable::parse($periodKey . '-28'),
        ]);

        $payout = Payout::create([
            'payout_batch_id' => $batch->id,
            'instructor_id' => $instructor->id,
            'instructor_type' => 'instructor',
            'period_key' => $periodKey,
            'amount_minor' => $amountMinor,
            'currency' => 'USD',
            'status' => Payout::STATUS_PENDING,
            'provider_idempotency_key' => (string)Str::uuid(),
            'attempts' => 0,
            'requires_manual_review' => false,
            'created_at' => CarbonImmutable::parse($periodKey . '-28'),
            'updated_at' => CarbonImmutable::parse($periodKey . '-28'),
        ]);

        foreach ($earnings as $earning) {
            PayoutItem::create([
                'payout_id' => $payout->id,
                'earning_schedule_id' => $earning->id,
                'amount_minor' => $earning->amount_minor,
            ]);
        }
    }
}
