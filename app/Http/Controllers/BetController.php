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
     * Display user's bets with filtering and statistics.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get filters from request
        $filters = $request->only(['sport_id', 'status', 'date_from', 'date_to']);

        // Build query with filters
        $query = Bet::with(['event.sport', 'odds.market'])
            ->where('user_id', $user->id);

        // Apply sport filter
        if (!empty($filters['sport_id'])) {
            $query->whereHas('event', function ($q) use ($filters) {
                $q->where('sport_id', $filters['sport_id']);
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $bets = $query->orderBy('created_at', 'desc')->get();

        // Group bets by status
        $pendingBets = $bets->where('status', 'pending');
        $settledBets = $bets->whereIn('status', ['won', 'lost', 'void']);

        // Calculate comprehensive statistics
        $wonBets = $bets->where('status', 'won');
        $lostBets = $bets->where('status', 'lost');
        $voidBets = $bets->where('status', 'void');

        $totalStaked = $bets->sum('stake');
        $totalReturns = $wonBets->sum('payout');
        $profitLoss = $totalReturns - ($totalStaked - $voidBets->sum('stake'));

        $settledCount = $wonBets->count() + $lostBets->count();
        $winRate = $settledCount > 0 ? ($wonBets->count() / $settledCount) * 100 : 0;

        // Get all sports for filter dropdown
        $sports = \App\Models\Sport::orderBy('name')->get();

        // Calculate performance over time (last 30 days)
        $performanceData = $this->calculatePerformanceData($user->id);

        return Inertia::render('Bets/Index', [
            'pendingBets' => $pendingBets->values(),
            'settledBets' => $settledBets->values(),
            'sports' => $sports,
            'filters' => $filters,
            'stats' => [
                'total_bets' => $bets->count(),
                'pending_count' => $pendingBets->count(),
                'won_count' => $wonBets->count(),
                'lost_count' => $lostBets->count(),
                'void_count' => $voidBets->count(),
                'total_staked' => $totalStaked,
                'total_returns' => $totalReturns,
                'profit_loss' => $profitLoss,
                'win_rate' => round($winRate, 2),
                'average_stake' => $bets->count() > 0 ? $totalStaked / $bets->count() : 0,
                'average_odds' => $bets->count() > 0 ? $bets->avg('odds_value') : 0,
            ],
            'performanceData' => $performanceData,
        ]);
    }

    /**
     * Calculate betting performance over time.
     */
    private function calculatePerformanceData($userId)
    {
        $thirtyDaysAgo = now()->subDays(30);

        $bets = Bet::where('user_id', $userId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['won', 'lost'])
            ->orderBy('created_at', 'asc')
            ->get();

        $data = [];
        $runningProfit = 0;

        foreach ($bets as $bet) {
            if ($bet->status === 'won') {
                $runningProfit += ($bet->payout - $bet->stake);
            } else {
                $runningProfit -= $bet->stake;
            }

            $data[] = [
                'date' => $bet->created_at->format('Y-m-d'),
                'profit' => round($runningProfit, 2),
            ];
        }

        return $data;
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
