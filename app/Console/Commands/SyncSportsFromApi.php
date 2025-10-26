<?php

namespace App\Console\Commands;

use App\Models\Sport;
use App\Services\OddsApiService;
use Illuminate\Console\Command;

class SyncSportsFromApi extends Command
{
    protected $signature = 'sports:sync';
    protected $description = 'Sync all available sports from TheOddsAPI';

    private OddsApiService $oddsApi;

    public function __construct(OddsApiService $oddsApi)
    {
        parent::__construct();
        $this->oddsApi = $oddsApi;
    }

    public function handle()
    {
        $this->info('🔄 Syncing sports from TheOddsAPI...');

        $apiSports = $this->oddsApi->getSports();

        if (empty($apiSports)) {
            $this->error('❌ Failed to fetch sports from API');
            return 1;
        }

        $this->info("Found " . count($apiSports) . " sports in API");

        $synced = 0;
        $skipped = 0;

        foreach ($apiSports as $apiSport) {
            // Skip outright markets (championship winners, etc.)
            if ($apiSport['has_outrights']) {
                $skipped++;
                continue;
            }

            $sport = Sport::updateOrCreate(
                ['slug' => $apiSport['key']],
                [
                    'name' => $apiSport['title'],
                    'icon' => $this->getIconForGroup($apiSport['group']),
                    'active' => true,
                    'api_key' => $apiSport['key'],
                    'api_group' => $apiSport['group'],
                ]
            );

            $synced++;
            $this->line("✅ {$apiSport['title']} ({$apiSport['key']})");
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   Synced: {$synced} sports");
        $this->info("   Skipped: {$skipped} outrights");

        return 0;
    }

    private function getIconForGroup(string $group): string
    {
        return match($group) {
            'American Football' => '🏈',
            'Basketball' => '🏀',
            'Soccer' => '⚽',
            'Baseball' => '⚾',
            'Ice Hockey' => '🏒',
            'Tennis' => '🎾',
            'Cricket' => '🏏',
            'Boxing' => '🥊',
            'Mixed Martial Arts' => '🥋',
            'Golf' => '⛳',
            'Handball' => '🤾',
            'Politics' => '🗳️',
            default => '🎯',
        };
    }
}
