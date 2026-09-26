<?php

namespace App\Models\Scopes;

trait EarningScheduleScopes
{
    public function scopeRecognized($query)
    {
        return $query->whereNotNull('recognized_at');
    }

    public function scopeNotVoided($query)
    {
        return $query->whereNull('voided_at');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereDoesntHave('payoutItem');
    }
}
