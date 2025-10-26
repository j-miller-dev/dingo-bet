<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odds;
use App\Services\OddsApiService;
use Illuminate\Console\Command;

class FetchLiveOdds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odds:fetch {--sport= : Specific sport to fetch} {--force : Force refresh cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch live odds from TheOddsAPI for upcoming events';

    private OddsApiService $oddsApi;

    public function __construct(OddsApiService $oddsApi)
    {
        parent::__construct();
        $this->oddsApi = $oddsApi;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎲 Fetching live odds from TheOddsAPI...');

        // Get upcoming events
        $events = Event::with(['sport', 'markets.odds'])
            ->where('status', 'upcoming')
            ->where('starts_at', '>', now())
            ->get();

        if ($events->isEmpty()) {
            $this->warn('No upcoming events found.');
            return 0;
        }

        $this->info("Found {$events->count()} upcoming events");

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($events as $event) {
            $sportKey = $this->oddsApi->mapSportKey($event->sport->name);

            if (!$sportKey) {
                $this->warn("⚠️  No API mapping for sport: {$event->sport->name}");
                continue;
            }

            $this->line("Fetching odds for: {$event->home_team} vs {$event->away_team}");

            // Clear cache if --force flag is used
            if ($this->option('force')) {
                $this->oddsApi->clearCache($sportKey);
            }

            // Fetch odds from API (cached)
            $apiOdds = $this->oddsApi->getOdds($sportKey);

            if (empty($apiOdds)) {
                $this->error("❌ Failed to fetch odds for {$event->sport->name}");
                $errorCount++;
                continue;
            }

            // Try to match event by team names
            $matchedGame = $this->findMatchingGame($apiOdds, $event);

            if (!$matchedGame) {
                $this->warn("⚠️  No matching game found in API for: {$event->home_team} vs {$event->away_team}");
                continue;
            }

            // Update odds for this event
            $this->updateEventOdds($event, $matchedGame);
            $updatedCount++;
            $this->info("✅ Updated odds for: {$event->home_team} vs {$event->away_team}");
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   Updated: {$updatedCount} events");
        if ($errorCount > 0) {
            $this->warn("   Errors: {$errorCount}");
        }

        $lastUpdated = $this->oddsApi->getLastUpdated($this->oddsApi->mapSportKey($events->first()->sport->name) ?? 'americanfootball_nfl');
        if ($lastUpdated) {
            $this->info("   Last API fetch: {$lastUpdated}");
        }

        return 0;
    }

    /**
     * Find matching game in API response.
     */
    private function findMatchingGame(array $apiOdds, Event $event): ?array
    {
        foreach ($apiOdds as $game) {
            $homeTeam = $game['home_team'] ?? '';
            $awayTeam = $game['away_team'] ?? '';

            // Simple matching - can be improved with fuzzy matching
            if (
                str_contains(strtolower($event->home_team), strtolower($homeTeam)) ||
                str_contains(strtolower($homeTeam), strtolower($event->home_team))
            ) {
                return $game;
            }
        }

        return null;
    }

    /**
     * Update odds for an event.
     */
    private function updateEventOdds(Event $event, array $gameData): void
    {
        // Get or create Match Winner market
        $market = Market::firstOrCreate(
            [
                'event_id' => $event->id,
                'type' => 'match_winner',
            ],
            [
                'name' => 'Match Winner',
                'active' => true,
            ]
        );

        // Get bookmakers data
        $bookmakers = $gameData['bookmakers'] ?? [];
        if (empty($bookmakers)) {
            return;
        }

        // Use first bookmaker's odds (usually best odds)
        $bookmaker = $bookmakers[0];
        $markets = $bookmaker['markets'] ?? [];

        foreach ($markets as $apiMarket) {
            if ($apiMarket['key'] !== 'h2h') {
                continue; // Only match winner for now
            }

            $outcomes = $apiMarket['outcomes'] ?? [];

            foreach ($outcomes as $outcome) {
                $name = $outcome['name'];
                $price = $outcome['price'];

                // Update or create odds
                Odds::updateOrCreate(
                    [
                        'market_id' => $market->id,
                        'name' => $name,
                    ],
                    [
                        'value' => $price,
                    ]
                );
            }
        }
    }
}
