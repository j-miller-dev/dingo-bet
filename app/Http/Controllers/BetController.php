<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Odds;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BetController extends Controller
{
    /**
     * Display user's bets.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's bets with event, sport, and odds info
        $bets = Bet::with(['event.sport', 'odds.market'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Group bets by status
        $pendingBets = $bets->where('status', 'pending');
        $settledBets = $bets->whereIn('status', ['won', 'lost', 'void']);

        return Inertia::render('Bets/Index', [
            'pendingBets' => $pendingBets->values(),
            'settledBets' => $settledBets->values(),
            'stats' => [
                'total_bets' => $bets->count(),
                'pending_count' => $pendingBets->count(),
                'won_count' => $bets->where('status', 'won')->count(),
                'lost_count' => $bets->where('status', 'lost')->count(),
                'total_staked' => $bets->sum('stake'),
                'total_returns' => $bets->where('status', 'won')->sum('payout'),
            ],
        ]);
    }

    /**
     * Store a new bet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'odds_id' => 'required|exists:odds,id',
            'stake' => 'required|numeric|min:1|max:10000',
        ]);

        $user = $request->user();
        $event = Event::findOrFail($validated['event_id']);
        $odds = Odds::with('market')->findOrFail($validated['odds_id']);

        // Validation 1: Check if event allows betting
        if (!$event->canBet()) {
            return back()->withErrors([
                'bet' => 'This event is no longer accepting bets.',
            ]);
        }

        // Validation 2: Check if odds belong to this event's market
        if ($odds->market->event_id !== $event->id) {
            return back()->withErrors([
                'bet' => 'Invalid odds selection for this event.',
            ]);
        }

        // Validation 3: Check if user has sufficient funds
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $validated['stake']) {
            return back()->withErrors([
                'bet' => 'Insufficient funds. Please add more play money to your wallet.',
            ]);
        }

        // Deduct stake from wallet
        try {
            $wallet->withdraw(
                $validated['stake'],
                "Bet placed on {$event->home_team} vs {$event->away_team}"
            );
        } catch (\Exception $e) {
            return back()->withErrors([
                'bet' => 'Unable to process bet: ' . $e->getMessage(),
            ]);
        }

        // Calculate payout based on odds
        $potentialPayout = $odds->calculatePayout($validated['stake']);

        // Create the bet
        $bet = Bet::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odds_id' => $odds->id,
            'stake' => $validated['stake'],
            'odds_value' => $odds->value, // Store odds value at time of bet
            'status' => 'pending',
            'payout' => $potentialPayout,
        ]);

        return redirect()
            ->route('bets.index')
            ->with('success', 'Bet placed successfully! Good luck!');
    }

    /**
     * Cancel a pending bet (refund).
     */
    public function cancel(Request $request, Bet $bet)
    {
        $user = $request->user();

        // Ensure user owns this bet
        if ($bet->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Can only cancel pending bets
        if ($bet->status !== 'pending') {
            return back()->withErrors([
                'bet' => 'Only pending bets can be cancelled.',
            ]);
        }

        // Can only cancel if event hasn't started
        if ($bet->event->hasStarted()) {
            return back()->withErrors([
                'bet' => 'Cannot cancel bet after event has started.',
            ]);
        }

        // Refund the stake
        $user->wallet->deposit(
            $bet->stake,
            "Bet cancelled - refund for {$bet->event->home_team} vs {$bet->event->away_team}"
        );

        // Mark bet as void
        $bet->update(['status' => 'void']);

        return back()->with('success', 'Bet cancelled and stake refunded.');
    }
}
