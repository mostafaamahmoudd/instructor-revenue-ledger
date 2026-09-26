<?php

namespace App\Models;

use App\Models\Relations\EarningScheduleRelations;
use App\Models\Scopes\EarningScheduleScopes;
use Database\Factories\EarningScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EarningSchedule extends Model
{
    /** @use HasFactory<EarningScheduleFactory> */
    use HasFactory;
    use EarningScheduleRelations;
    use EarningScheduleScopes;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'allocation_id',
        'instructor_id',
        'earn_date',
        'amount_minor',
        'currency',
        'recognized_at',
        'voided_at', 'voided_by_refund_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'earn_date' => 'date',
            'recognized_at' => 'datetime',
            'voided_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
