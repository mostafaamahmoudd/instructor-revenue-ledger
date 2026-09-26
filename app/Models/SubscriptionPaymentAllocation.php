<?php

namespace App\Models;

use App\Models\Relations\SubscriptionPaymentAllocationRelations;
use Database\Factories\SubscriptionPaymentAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentAllocation extends Model
{
    /** @use HasFactory<SubscriptionPaymentAllocationFactory> */
    use HasFactory;
    use SubscriptionPaymentAllocationRelations;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_payment_id',
        'instructor_id',
        'amount_minor',
        'currency',
        'created_at'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime'
        ];
    }
}
