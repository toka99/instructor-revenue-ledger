<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructor extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function getTotalEarnedCentsAttribute(): int
    {
        return $this->ledgerEntries()->where('type', 'credit')->sum('amount_cents');
    }

    public function getTotalPaidCentsAttribute(): int
    {
        return $this->ledgerEntries()->where('type', 'debit')->sum('amount_cents');
    }
}