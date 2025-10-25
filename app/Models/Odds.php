<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Odds extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'market_id',
        'name',
        'value',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Get the market this odds belongs to.
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * Calculate payout for a given stake.
     */
    public function calculatePayout(float $stake): float
    {
        return round($stake * $this->value, 2);
    }

    /**
     * Calculate profit for a given stake.
     */
    public function calculateProfit(float $stake): float
    {
        return round($this->calculatePayout($stake) - $stake, 2);
    }

    /**
     * Scope for active odds.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
