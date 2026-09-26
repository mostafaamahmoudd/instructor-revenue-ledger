<?php

namespace App\Models;

use App\Models\Relations\InstructorBalanceSnapshotRelations;
use Database\Factories\InstructorBalanceSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructorBalanceSnapshot extends Model
{
    /** @use HasFactory<InstructorBalanceSnapshotFactory> */
    use HasFactory;
    use InstructorBalanceSnapshotRelations;

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'instructor_id',
        'currency',
        'earned_total_minor',
        'paid_total_minor',
        'computed_at'
    ];

    protected function casts(): array
    {
        return ['computed_at' => 'datetime'];
    }
}
