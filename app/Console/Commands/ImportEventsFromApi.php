<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odds;
use App\Models\Sport;
use App\Services\OddsApiService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ImportEventsFromApi extends Command
{
    protected $signature = 'events:import
                            {--limit=5 : Max sports to import from (to save API credits)}
                            {--markets=h2h : Comma-separated markets to import}';

    protected $description = 'Import real upcoming events from TheOddsAPI';

    private OddsApiService $oddsApi;

    public function __construct(OddsApiService $oddsApi)
    {
        parent::__construct();
        $this->oddsApi = $oddsApi;
    }

    public function handle()
    {
        $this->info('📥 Importing upcoming events from TheOddsAPI...');

        $limit = (int) $this->option('limit');
        $markets = explode(',', $this->option('markets'));

        // Get active sports with API keys
        $sports = Sport::whereNotNull('api_key')
            ->where('active', true)
            ->limit($limit)
            ->get();

        if ($sports->isEmpty()) {
            $this->warn('No active sports found. Run: php artisan sports:sync');
            return 1;
        }

        $this->info("Importing from {$sports->count()} sports (limit: {$limit})");
        $this->info("Markets: " . implode(', ', $markets));
        $this->newLine();

        $totalImported = 0;
        $totalSkipped = 0;

        foreach ($sports as $sport) {
            $this->line("Fetching games for: {$sport->name}");

            // Fetch games for first market type
            $marketType = trim($markets[0]);
            $apiGames = $this->oddsApi->getOdds($sport->api_key, 'us', $marketType);

            if (empty($apiGames)) {
                $this->warn("  No games found");
                continue;
            }

            $imported = 0;

            foreach ($apiGames as $game) {
                $homeTeam = $game['home_team'] ?? null;
                $awayTeam = $game['away_team'] ?? null;
                $commenceTime = $game['commence_time'] ?? null;

                if (!$homeTeam || !$awayTeam || !$commenceTime) {
                    continue;
                }

                // Check if event already exists
                $exists = Event::where('sport_id', $sport->id)
                    ->where('home_team', $homeTeam)
                    ->where('away_team', $awayTeam)
                    ->where('starts_at', '>=', now()->subHours(6))
                    ->exists();

                if ($exists) {
                    $totalSkipped++;
                    continue;
                }

                // Create event
                $event = Event::create([
                    'sport_id' => $sport->id,
                    'home_team' => $homeTeam,
                    'away_team' => $awayTeam,
                    'starts_at' => Carbon::parse($commenceTime),
                    'status' => 'upcoming',
                ]);

                // Import odds for all requested markets
                foreach ($markets as $marketType) {
                    $marketType = trim($marketType);
                    $this->importOddsForEvent($event, $game, $marketType);
                }

                $imported++;
                $totalImported++;
            }

            $this->info("  ✅ Imported {$imported} games");
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   Imported: {$totalImported} new events");
        $this->info("   Skipped: {$totalSkipped} duplicates");
        $this->info("   API Credits Used: ~" . ($sports->count() * count($markets)));

        return 0;
    }

    private function importOddsForEvent(Event $event, array $gameData, string $marketType): void
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

            $marketName = $this->getMarketName($marketType);
            $market = Market::create([
                'event_id' => $event->id,
                'name' => $marketName,
                'type' => $marketType,
                'active' => true,
            ]);

            $outcomes = $apiMarket['outcomes'] ?? [];

            foreach ($outcomes as $outcome) {
                $name = $outcome['name'];
                $price = $outcome['price'];
                $point = $outcome['point'] ?? null;

                if ($point !== null) {
                    $name = "{$name} ({$point})";
                }

                Odds::create([
                    'market_id' => $market->id,
                    'name' => $name,
                    'value' => $price,
                ]);
            }
        }
    }

    private function getMarketName(string $marketType): string
    {
        return match($marketType) {
            'h2h' => 'Match Winner',
            'spreads' => 'Point Spread',
            'totals' => 'Over/Under',
            default => ucfirst(str_replace('_', ' ', $marketType)),
        };
    }
}
