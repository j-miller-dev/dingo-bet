<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odds;
use App\Services\OddsApiService;
use Illuminate\Console\Command;

class FetchLiveOdds extends Command
{
    protected $signature = 'odds:fetch
                            {--sport= : Specific sport API key to fetch}
                            {--force : Force refresh cache}
                            {--markets=h2h,spreads,totals : Comma-separated markets}';

    protected $description = 'Fetch live odds from TheOddsAPI (supports all markets)';

    private OddsApiService $oddsApi;

    public function __construct(OddsApiService $oddsApi)
    {
        parent::__construct();
        $this->oddsApi = $oddsApi;
    }

    public function handle()
    {
        $this->info('🎲 Fetching live odds from TheOddsAPI...');

        $markets = explode(',', $this->option('markets'));
        $this->info("Markets: " . implode(', ', $markets));

        // Get upcoming events
        $events = Event::with(['sport', 'markets.odds'])
            ->where('status', 'upcoming')
            ->where('starts_at', '>', now())
            ->get();

        if ($events->isEmpty()) {
            $this->warn('No upcoming events found.');
            return 0;
        }

        $this->info("Found {$events->count()} upcoming events\n");

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($events as $event) {
            // Use api_key if available, otherwise try legacy mapping
            $sportKey = $event->sport->api_key ?? $this->oddsApi->mapSportKey($event->sport->name);

            if (!$sportKey) {
                $this->warn("⚠️  No API key for sport: {$event->sport->name}");
                continue;
            }

            $this->line("Fetching odds for: {$event->home_team} vs {$event->away_team}");

            $eventUpdated = false;

            // Fetch odds for each market type
            foreach ($markets as $marketType) {
                $marketType = trim($marketType);

                if ($this->option('force')) {
                    $this->oddsApi->clearCache($sportKey, 'us', $marketType);
                }

                $apiOdds = $this->oddsApi->getOdds($sportKey, 'us', $marketType);

                if (empty($apiOdds)) {
                    continue;
                }

                $matchedGame = $this->findMatchingGame($apiOdds, $event);

                if (!$matchedGame) {
                    continue;
                }

                $this->updateEventOdds($event, $matchedGame, $marketType);
                $eventUpdated = true;
            }

            if ($eventUpdated) {
                $updatedCount++;
                $this->info("✅ Updated odds for: {$event->home_team} vs {$event->away_team}");
            } else {
                $this->warn("⚠️  No matching game found in API");
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   Updated: {$updatedCount} events");
        if ($errorCount > 0) {
            $this->warn("   Not found: {$errorCount}");
        }

        return 0;
    }

    private function findMatchingGame(array $apiOdds, Event $event): ?array
    {
        foreach ($apiOdds as $game) {
            $homeTeam = $game['home_team'] ?? '';
            $awayTeam = $game['away_team'] ?? '';

            if (
                str_contains(strtolower($event->home_team), strtolower($homeTeam)) ||
                str_contains(strtolower($homeTeam), strtolower($event->home_team))
            ) {
                return $game;
            }
        }

        return null;
    }

    private function updateEventOdds(Event $event, array $gameData, string $marketType): void
    {
        $bookmakers = $gameData['bookmakers'] ?? [];
        if (empty($bookmakers)) {
            return;
        }

        $bookmaker = $bookmakers[0];
        $apiMarkets = $bookmaker['markets'] ?? [];

        foreach ($apiMarkets as $apiMarket) {
            if ($apiMarket['key'] !== $marketType) {
                continue;
            }

            // Create market based on type
            $marketName = $this->getMarketName($marketType);
            $market = Market::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'type' => $marketType,
                ],
                [
                    'name' => $marketName,
                    'active' => true,
                ]
            );

            $outcomes = $apiMarket['outcomes'] ?? [];

            foreach ($outcomes as $outcome) {
                $name = $outcome['name'];
                $price = $outcome['price'];
                $point = $outcome['point'] ?? null;

                // For spreads/totals, include the point in the name
                if ($point !== null) {
                    $name = "{$name} ({$point})";
                }

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

    private function getMarketName(string $marketType): string
    {
        return match($marketType) {
            'h2h' => 'Match Winner',
            'spreads' => 'Point Spread',
            'totals' => 'Over/Under',
            'outrights' => 'Outright Winner',
            default => ucfirst(str_replace('_', ' ', $marketType)),
        };
    }
}
