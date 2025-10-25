<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettlementController extends Controller
{
    /**
     * Show events that need settlement.
     */
    public function index()
    {
        // Get events that have started but aren't settled yet
        $unsettledEvents = Event::with(['sport', 'bets' => function ($query) {
            $query->where('status', 'pending');
        }])
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '<', now())
            ->orderBy('starts_at', 'desc')
            ->get()
            ->map(function ($event) {
                $event->pending_bets_count = $event->bets->count();
                $event->total_pending_stake = $event->bets->sum('stake');
                return $event;
            });

        // Get recently settled events
        $recentlySettled = Event::with('sport')
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Settlement/Index', [
            'unsettledEvents' => $unsettledEvents,
            'recentlySettled' => $recentlySettled,
        ]);
    }

    /**
     * Settle an event.
     */
    public function settle(Request $request, Event $event)
    {
        $validated = $request->validate([
            'winner' => 'required|in:home,away,draw',
        ]);

        try {
            $stats = $event->settleBets($validated['winner']);

            return redirect()
                ->route('settlement.index')
                ->with('success', "Event settled! {$stats['winning_bets']} winners paid out \${$stats['total_payout']}.");
        } catch (\Exception $e) {
            return back()->withErrors([
                'settlement' => 'Failed to settle event: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Void an event (cancel).
     */
    public function void(Request $request, Event $event)
    {
        try {
            $stats = $event->voidBets();

            return redirect()
                ->route('settlement.index')
                ->with('success', "Event cancelled. {$stats['total_bets']} bets refunded \${$stats['total_refunded']}.");
        } catch (\Exception $e) {
            return back()->withErrors([
                'settlement' => 'Failed to void event: ' . $e->getMessage(),
            ]);
        }
    }
}
