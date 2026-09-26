<?php

namespace App\Models;

use App\Models\Relations\PayoutItemRelations;
use Database\Factories\PayoutItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    /** @use HasFactory<PayoutItemFactory> */
    use HasFactory;
    use PayoutItemRelations;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payout_id',
        'earning_schedule_id',
        'amount_minor'
    ];
}
