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

        // Get all sports for filter
        $sports = Sport::where('active', true)
            ->withCount('events')
            ->orderBy('name')
            ->get();

        // Build events query
        $eventsQuery = Event::with('sport')
            ->upcoming()
            ->limit(50);

        // Filter by sport if provided
        if ($sportSlug) {
            $sport = Sport::where('slug', $sportSlug)->first();
            if ($sport) {
                $eventsQuery->where('sport_id', $sport->id);
            }
        }

        $events = $eventsQuery->get();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'sports' => $sports,
            'selectedSport' => $sportSlug,
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
