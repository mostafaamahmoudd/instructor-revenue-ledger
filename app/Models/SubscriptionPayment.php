<?php

namespace App\Models;

use App\Models\Relations\SubscriptionPaymentRelations;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;
    use SubscriptionPaymentRelations;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'amount_minor',
        'platform_cut_minor',
        'currency',
        'idempotency_key',
        'provider_reference',
        'status',
        'paid_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'created_at' => 'datetime'
        ];
    }
}
