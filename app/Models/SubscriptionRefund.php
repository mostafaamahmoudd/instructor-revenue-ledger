<?php

namespace App\Models;

use App\Models\Relations\SubscriptionRefundRelations;
use Database\Factories\SubscriptionRefundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionRefund extends Model
{
    /** @use HasFactory<SubscriptionRefundFactory> */
    use HasFactory;
    use SubscriptionRefundRelations;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'amount_minor',
        'idempotency_key',
        'status',
        'requested_at',
        'processed_at',
        'created_at'
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'created_at' => 'datetime'
        ];
    }

}
