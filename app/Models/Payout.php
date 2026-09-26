<?php

namespace App\Models;

use App\Models\Relations\PayoutRelations;
use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
    use HasFactory;
    use PayoutRelations;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNCERTAIN = 'uncertain';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payout_batch_id',
        'instructor_id',
        'instructor_type',
        'period_key',
        'amount_minor',
        'currency',
        'status',
        'provider_idempotency_key',
        'provider_reference',
        'attempts',
        'requires_manual_review',
        'last_checked_at',

    ];

    protected function casts(): array
    {
        return [
            'requires_manual_review' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }
}
