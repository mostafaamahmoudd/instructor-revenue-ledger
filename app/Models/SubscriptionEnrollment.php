<?php

namespace App\Models;

use App\Models\Relations\SubscriptionEnrollmentRelations;
use Database\Factories\SubscriptionEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionEnrollment extends Model
{
    /** @use HasFactory<SubscriptionEnrollmentFactory> */
    use HasFactory;
    use SubscriptionEnrollmentRelations;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_payment_id',
        'instructor_id',
        'course_id'
    ];
}
