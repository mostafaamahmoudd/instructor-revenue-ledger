<?php

namespace App\Models;

use App\Models\Relations\PayoutBatchRelations;
use Database\Factories\PayoutBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    /** @use HasFactory<PayoutBatchFactory> */
    use HasFactory;
    use PayoutBatchRelations;

    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'period_key',
        'triggered_at',
        'status',
        'created_at'
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'created_at' => 'datetime'
        ];
    }
}
