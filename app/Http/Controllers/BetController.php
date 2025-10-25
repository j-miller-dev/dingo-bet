<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Event;
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

        // Get user's bets with event and sport info
        $bets = Bet::with(['event.sport'])
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
            'selection' => 'required|in:home,away,draw',
            'stake' => 'required|numeric|min:1|max:10000',
        ]);

        $user = $request->user();
        $event = Event::findOrFail($validated['event_id']);

        // Validation 1: Check if event allows betting
        if (!$event->canBet()) {
            return back()->withErrors([
                'bet' => 'This event is no longer accepting bets.',
            ]);
        }

        // Validation 2: Check if user has sufficient funds
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

        // Create the bet
        // For Phase 3, we use simple 2x payout if won (we'll add real odds in Phase 4)
        $potentialPayout = $validated['stake'] * 2;

        $bet = Bet::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'selection' => $validated['selection'],
            'stake' => $validated['stake'],
            'status' => 'pending',
            'payout' => $potentialPayout, // Store potential payout
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
