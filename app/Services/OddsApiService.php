<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OddsApiService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.the-odds-api.com/v4';
    private int $cacheHours;

    public function __construct()
    {
        $this->apiKey = config('services.odds_api.key');
        $this->cacheHours = config('services.odds_api.cache_hours', 6);
    }

    /**
     * Get available sports from TheOddsAPI.
     * Cached heavily to save credits.
     */
    public function getSports(): array
    {
        $cacheKey = 'odds_api_sports';

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            try {
                $response = Http::get("{$this->baseUrl}/sports", [
                    'apiKey' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $this->logRemainingRequests($response);
                    return $response->json();
                }

                Log::error('OddsAPI Sports Error', ['response' => $response->body()]);
                return [];
            } catch (\Exception $e) {
                Log::error('OddsAPI Sports Exception', ['message' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get odds for a specific sport.
     * Cached for several hours to conserve API credits.
     *
     * @param string $sportKey - e.g., 'americanfootball_nfl', 'basketball_nba'
     * @param string $region - 'us', 'uk', 'eu', 'au'
     * @param string $market - 'h2h' (moneyline), 'spreads', 'totals'
     */
    public function getOdds(string $sportKey, string $region = 'us', string $market = 'h2h'): array
    {
        $cacheKey = "odds_api_{$sportKey}_{$region}_{$market}";

        return Cache::remember($cacheKey, now()->addHours($this->cacheHours), function () use ($sportKey, $region, $market) {
            try {
                $response = Http::get("{$this->baseUrl}/sports/{$sportKey}/odds", [
                    'apiKey' => $this->apiKey,
                    'regions' => $region,
                    'markets' => $market,
                    'oddsFormat' => 'decimal',
                ]);

                if ($response->successful()) {
                    $this->logRemainingRequests($response);
                    $data = $response->json();

                    // Store last updated timestamp
                    Cache::put("{$cacheKey}_updated_at", now(), now()->addHours($this->cacheHours));

                    return $data;
                }

                Log::error('OddsAPI Odds Error', [
                    'sport' => $sportKey,
                    'response' => $response->body(),
                ]);
                return [];
            } catch (\Exception $e) {
                Log::error('OddsAPI Odds Exception', [
                    'sport' => $sportKey,
                    'message' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    /**
     * Get when odds were last updated.
     */
    public function getLastUpdated(string $sportKey, string $region = 'us', string $market = 'h2h'): ?string
    {
        $cacheKey = "odds_api_{$sportKey}_{$region}_{$market}_updated_at";
        return Cache::get($cacheKey)?->toDateTimeString();
    }

    /**
     * Clear cache for specific sport to force refresh.
     * Use sparingly to conserve API credits.
     */
    public function clearCache(string $sportKey, string $region = 'us', string $market = 'h2h'): void
    {
        $cacheKey = "odds_api_{$sportKey}_{$region}_{$market}";
        Cache::forget($cacheKey);
        Cache::forget("{$cacheKey}_updated_at");
    }

    /**
     * Clear all odds cache.
     * WARNING: Next request will use API credits!
     */
    public function clearAllCache(): void
    {
        Cache::flush();
    }

    /**
     * Get remaining API requests from response headers.
     */
    private function logRemainingRequests($response): void
    {
        $remaining = $response->header('x-requests-remaining');
        $used = $response->header('x-requests-used');

        if ($remaining !== null) {
            Log::info('OddsAPI Credits', [
                'remaining' => $remaining,
                'used' => $used,
            ]);
        }
    }

    /**
     * Map TheOddsAPI sport keys to our sport names.
     */
    public function mapSportKey(string $ourSportName): ?string
    {
        $mapping = [
            'Football' => 'americanfootball_nfl',
            'Basketball' => 'basketball_nba',
            'Soccer' => 'soccer_epl', // English Premier League
            'Tennis' => 'tennis_atp', // ATP Tour
        ];

        return $mapping[$ourSportName] ?? null;
    }
}
