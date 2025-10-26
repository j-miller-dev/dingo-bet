<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Platform statistics
        $totalUsers = User::count();
        $totalEvents = Event::count();
        $totalBets = Bet::count();
        $totalStaked = Bet::sum('stake');
        $totalPayouts = Bet::where('status', 'won')->sum('payout');
        $platformProfit = $totalStaked - $totalPayouts;

        // Recent activity
        $recentBets = Bet::with(['user', 'event.sport', 'odds'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Events needing settlement
        $unsettledEvents = Event::with('sport')
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '<', now())
            ->withCount(['bets' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->get();

        // Sports statistics
        $sportStats = Sport::withCount(['events', 'events as upcoming_events_count' => function ($query) {
            $query->where('status', 'upcoming');
        }])->get();

        // User activity stats
        $activeUsers = Bet::where('created_at', '>=', now()->subDays(7))
            ->distinct('user_id')
            ->count('user_id');

        // Bet status breakdown
        $betStats = [
            'pending' => Bet::where('status', 'pending')->count(),
            'won' => Bet::where('status', 'won')->count(),
            'lost' => Bet::where('status', 'lost')->count(),
            'void' => Bet::where('status', 'void')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_events' => $totalEvents,
                'total_bets' => $totalBets,
                'total_staked' => $totalStaked,
                'total_payouts' => $totalPayouts,
                'platform_profit' => $platformProfit,
            ],
            'betStats' => $betStats,
            'recentBets' => $recentBets,
            'recentUsers' => $recentUsers,
            'unsettledEvents' => $unsettledEvents,
            'sportStats' => $sportStats,
        ]);
    }

    /**
     * Display user management page.
     */
    public function users()
    {
        $users = User::withCount('bets')
            ->with('wallet')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                $wonBets = Bet::where('user_id', $user->id)->where('status', 'won')->sum('payout');
                $totalStaked = Bet::where('user_id', $user->id)->sum('stake');

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'created_at' => $user->created_at,
                    'bets_count' => $user->bets_count,
                    'wallet_balance' => $user->wallet ? $user->wallet->balance : 0,
                    'total_staked' => $totalStaked,
                    'total_won' => $wonBets,
                    'profit_loss' => $wonBets - $totalStaked,
                ];
            });

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    /**
     * Toggle admin status for a user.
     */
    public function toggleAdmin(User $user)
    {
        $user->update(['is_admin' => !$user->is_admin]);

        return back()->with('success', "User {$user->name} admin status updated.");
    }
}
