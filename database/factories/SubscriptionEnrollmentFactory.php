<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\SubscriptionEnrollment;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionEnrollment>
 */
class SubscriptionEnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_payment_id' => SubscriptionPayment::factory(),
            'instructor_id' => Instructor::factory(),
            'course_id' => Course::factory(),
        ];
    }
}
