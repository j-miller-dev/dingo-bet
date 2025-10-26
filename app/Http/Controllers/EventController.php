<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Sport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request)
    {
        $sportSlug = $request->query('sport');
        $group = $request->query('group');

        // Get all sports for filter, grouped by category
        $allSports = Sport::where('active', true)
            ->withCount('events')
            ->orderBy('api_group')
            ->orderBy('name')
            ->get();

        // Group sports by category
        $sportGroups = $allSports->groupBy('api_group')->map(function ($sports, $groupName) {
            return [
                'name' => $groupName ?: 'Other',
                'sports' => $sports,
                'total_events' => $sports->sum('events_count'),
            ];
        })->sortByDesc('total_events');

        // Build events query
        $eventsQuery = Event::with('sport')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at', 'asc');

        // Filter by sport group if provided
        if ($group) {
            $eventsQuery->whereHas('sport', function ($query) use ($group) {
                $query->where('api_group', $group);
            });
        }

        // Filter by specific sport if provided
        if ($sportSlug) {
            $sport = Sport::where('slug', $sportSlug)->first();
            if ($sport) {
                $eventsQuery->where('sport_id', $sport->id);
            }
        }

        $events = $eventsQuery->limit(100)->get();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'sportGroups' => $sportGroups->values(),
            'selectedSport' => $sportSlug,
            'selectedGroup' => $group,
        ]);
    }

    /**
     * Display a specific event.
     */
    public function show(Request $request, Event $event)
    {
        $event->load(['sport', 'markets.odds' => function ($query) {
            $query->where('active', true);
        }]);

        $user = $request->user();
        $wallet = $user->wallet;

        return Inertia::render('Events/Show', [
            'event' => $event,
            'wallet' => $wallet,
        ]);
    }
}
