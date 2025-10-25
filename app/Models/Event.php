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
     * Get all markets for this event.
     */
    public function markets(): HasMany
    {
        return $this->hasMany(Market::class);
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

    /**
     * Settle all pending bets for this event.
     *
     * @param string $winner - 'home', 'away', or 'draw'
     * @return array Stats about settlement
     */
    public function settleBets(string $winner): array
    {
        // Validate winner
        if (!in_array($winner, ['home', 'away', 'draw'])) {
            throw new \InvalidArgumentException('Winner must be home, away, or draw');
        }

        // Update event status and result
        $this->update([
            'status' => 'completed',
            'result' => $winner,
        ]);

        // Get all pending bets
        $pendingBets = $this->bets()->where('status', 'pending')->with(['odds', 'user.wallet'])->get();

        $stats = [
            'total_bets' => $pendingBets->count(),
            'winning_bets' => 0,
            'losing_bets' => 0,
            'total_payout' => 0,
        ];

        foreach ($pendingBets as $bet) {
            if ($this->isBetWinner($bet, $winner)) {
                // Bet wins - pay out
                $bet->user->wallet->deposit(
                    $bet->payout,
                    "Bet won on {$this->home_team} vs {$this->away_team}"
                );

                $bet->update(['status' => 'won']);
                $stats['winning_bets']++;
                $stats['total_payout'] += $bet->payout;
            } else {
                // Bet loses
                $bet->update(['status' => 'lost']);
                $stats['losing_bets']++;
            }
        }

        return $stats;
    }

    /**
     * Determine if a bet is a winner based on event result.
     */
    private function isBetWinner(Bet $bet, string $winner): bool
    {
        if (!$bet->odds) {
            return false;
        }

        $oddsName = strtolower($bet->odds->name);
        $homeTeam = strtolower($this->home_team);
        $awayTeam = strtolower($this->away_team);

        // Match Winner market - most common
        if ($winner === 'home' && $oddsName === $homeTeam) {
            return true;
        }

        if ($winner === 'away' && $oddsName === $awayTeam) {
            return true;
        }

        if ($winner === 'draw' && $oddsName === 'draw') {
            return true;
        }

        // For other markets (Over/Under, etc.), we'd need more complex logic
        // For Phase 5, we'll focus on Match Winner and mark others as void
        // You can expand this later to handle other market types

        return false;
    }

    /**
     * Void all pending bets (cancelled event).
     */
    public function voidBets(): array
    {
        $this->update([
            'status' => 'cancelled',
        ]);

        $pendingBets = $this->bets()->where('status', 'pending')->with('user.wallet')->get();

        $stats = [
            'total_bets' => $pendingBets->count(),
            'total_refunded' => 0,
        ];

        foreach ($pendingBets as $bet) {
            // Refund stake
            $bet->user->wallet->deposit(
                $bet->stake,
                "Bet voided - event cancelled: {$this->home_team} vs {$this->away_team}"
            );

            $bet->update(['status' => 'void']);
            $stats['total_refunded'] += $bet->stake;
        }

        return $stats;
    }
}
