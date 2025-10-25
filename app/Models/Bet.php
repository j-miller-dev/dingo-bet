<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_id',
        'odds_id',
        'stake',
        'selection',
        'odds_value',
        'status',
        'payout',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stake' => 'decimal:2',
        'odds_value' => 'decimal:2',
        'payout' => 'decimal:2',
    ];

    /**
     * Get the user who placed the bet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event this bet is on.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the odds for this bet.
     */
    public function odds(): BelongsTo
    {
        return $this->belongsTo(Odds::class);
    }

    /**
     * Check if bet is still pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if bet was won.
     */
    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    /**
     * Check if bet was lost.
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Get the selected team name.
     */
    public function getSelectedTeamAttribute(): string
    {
        if ($this->selection === 'home') {
            return $this->event->home_team;
        } elseif ($this->selection === 'away') {
            return $this->event->away_team;
        }
        return 'Draw';
    }

    /**
     * Scope for pending bets.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for settled bets (won or lost).
     */
    public function scopeSettled($query)
    {
        return $query->whereIn('status', ['won', 'lost']);
    }

    /**
     * Scope for user's bets.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
