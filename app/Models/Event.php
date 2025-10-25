<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sport_id',
        'home_team',
        'away_team',
        'starts_at',
        'status',
        'result',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
    ];

    /**
     * Get the sport this event belongs to.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get all bets placed on this event.
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Check if event has started.
     */
    public function hasStarted(): bool
    {
        return $this->starts_at->isPast();
    }

    /**
     * Check if betting is still allowed.
     */
    public function canBet(): bool
    {
        return $this->status === 'upcoming' && !$this->hasStarted();
    }

    /**
     * Scope for upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at');
    }

    /**
     * Scope for live events.
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    /**
     * Scope for completed events.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
