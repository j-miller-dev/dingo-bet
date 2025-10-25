<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odds;
use Illuminate\Database\Seeder;

class MarketsAndOddsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::all();

        foreach ($events as $event) {
            $this->createMarketsForEvent($event);
        }

        $this->command->info('✅ Created markets and odds for all events');
    }

    private function createMarketsForEvent($event)
    {
        $sportName = $event->sport->name;

        // Match Winner market (for all sports)
        $matchWinner = Market::create([
            'event_id' => $event->id,
            'name' => 'Match Winner',
            'type' => 'match_winner',
            'active' => true,
        ]);

        // Create randomized but realistic odds
        $homeOdds = $this->randomOdds(1.50, 3.50);
        $awayOdds = $this->randomOdds(1.50, 3.50);

        Odds::create([
            'market_id' => $matchWinner->id,
            'name' => $event->home_team,
            'value' => $homeOdds,
            'active' => true,
        ]);

        Odds::create([
            'market_id' => $matchWinner->id,
            'name' => $event->away_team,
            'value' => $awayOdds,
            'active' => true,
        ]);

        // Add Draw option for soccer and football
        if (in_array($sportName, ['Soccer', 'Football'])) {
            Odds::create([
                'market_id' => $matchWinner->id,
                'name' => 'Draw',
                'value' => $this->randomOdds(2.80, 3.80),
                'active' => true,
            ]);
        }

        // Over/Under market
        if (in_array($sportName, ['Football', 'Basketball', 'Soccer'])) {
            $overUnder = Market::create([
                'event_id' => $event->id,
                'name' => $this->getOverUnderName($sportName),
                'type' => 'over_under',
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $overUnder->id,
                'name' => 'Over',
                'value' => $this->randomOdds(1.80, 2.10),
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $overUnder->id,
                'name' => 'Under',
                'value' => $this->randomOdds(1.80, 2.10),
                'active' => true,
            ]);
        }

        // Both Teams to Score (Soccer/Football only)
        if (in_array($sportName, ['Soccer', 'Football'])) {
            $btts = Market::create([
                'event_id' => $event->id,
                'name' => 'Both Teams to Score',
                'type' => 'both_teams_score',
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $btts->id,
                'name' => 'Yes',
                'value' => $this->randomOdds(1.60, 2.00),
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $btts->id,
                'name' => 'No',
                'value' => $this->randomOdds(1.80, 2.40),
                'active' => true,
            ]);
        }

        // Handicap market (Basketball only)
        if ($sportName === 'Basketball') {
            $handicap = Market::create([
                'event_id' => $event->id,
                'name' => 'Point Spread',
                'type' => 'handicap',
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $handicap->id,
                'name' => $event->home_team . ' -5.5',
                'value' => 1.90,
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $handicap->id,
                'name' => $event->away_team . ' +5.5',
                'value' => 1.90,
                'active' => true,
            ]);
        }

        // Set winner (Tennis only)
        if ($sportName === 'Tennis') {
            $sets = Market::create([
                'event_id' => $event->id,
                'name' => 'Total Sets',
                'type' => 'total_sets',
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $sets->id,
                'name' => 'Over 2.5 Sets',
                'value' => $this->randomOdds(1.70, 2.20),
                'active' => true,
            ]);

            Odds::create([
                'market_id' => $sets->id,
                'name' => 'Under 2.5 Sets',
                'value' => $this->randomOdds(1.70, 2.20),
                'active' => true,
            ]);
        }
    }

    private function getOverUnderName($sportName)
    {
        switch ($sportName) {
            case 'Football':
                return 'Total Points Over/Under 45.5';
            case 'Basketball':
                return 'Total Points Over/Under 215.5';
            case 'Soccer':
                return 'Total Goals Over/Under 2.5';
            default:
                return 'Over/Under';
        }
    }

    private function randomOdds($min, $max)
    {
        // Generate random odds in 0.05 increments (e.g., 1.85, 1.90, 1.95)
        $range = ($max - $min) / 0.05;
        $random = rand(0, $range);
        return round($min + ($random * 0.05), 2);
    }
}
