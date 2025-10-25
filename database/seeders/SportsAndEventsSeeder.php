<?php

namespace Database\Seeders;

use App\Models\Sport;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SportsAndEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Sports
        $football = Sport::create([
            'name' => 'Football',
            'slug' => 'football',
            'icon' => '🏈',
            'active' => true,
        ]);

        $basketball = Sport::create([
            'name' => 'Basketball',
            'slug' => 'basketball',
            'icon' => '🏀',
            'active' => true,
        ]);

        $soccer = Sport::create([
            'name' => 'Soccer',
            'slug' => 'soccer',
            'icon' => '⚽',
            'active' => true,
        ]);

        $tennis = Sport::create([
            'name' => 'Tennis',
            'slug' => 'tennis',
            'icon' => '🎾',
            'active' => true,
        ]);

        // Create Football Events (NFL)
        // Past events that need settlement
        Event::create([
            'sport_id' => $football->id,
            'home_team' => 'New England Patriots',
            'away_team' => 'Kansas City Chiefs',
            'starts_at' => Carbon::now()->subHours(3),
            'status' => 'live',
        ]);

        Event::create([
            'sport_id' => $football->id,
            'home_team' => 'Dallas Cowboys',
            'away_team' => 'Green Bay Packers',
            'starts_at' => Carbon::now()->subDays(1)->setTime(13, 0),
            'status' => 'live',
        ]);

        // Future events
        Event::create([
            'sport_id' => $football->id,
            'home_team' => 'San Francisco 49ers',
            'away_team' => 'Seattle Seahawks',
            'starts_at' => Carbon::now()->addDays(5)->setTime(16, 30),
            'status' => 'upcoming',
        ]);

        // Create Basketball Events (NBA)
        // Past event that needs settlement
        Event::create([
            'sport_id' => $basketball->id,
            'home_team' => 'Los Angeles Lakers',
            'away_team' => 'Boston Celtics',
            'starts_at' => Carbon::now()->subHours(5),
            'status' => 'live',
        ]);

        // Future events
        Event::create([
            'sport_id' => $basketball->id,
            'home_team' => 'Golden State Warriors',
            'away_team' => 'Chicago Bulls',
            'starts_at' => Carbon::now()->addDays(2)->setTime(22, 0),
            'status' => 'upcoming',
        ]);

        Event::create([
            'sport_id' => $basketball->id,
            'home_team' => 'Miami Heat',
            'away_team' => 'Brooklyn Nets',
            'starts_at' => Carbon::now()->addDays(4)->setTime(19, 0),
            'status' => 'upcoming',
        ]);

        // Create Soccer Events (Premier League)
        // Past event that needs settlement
        Event::create([
            'sport_id' => $soccer->id,
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'starts_at' => Carbon::now()->subHours(2),
            'status' => 'live',
        ]);

        // Future events
        Event::create([
            'sport_id' => $soccer->id,
            'home_team' => 'Manchester United',
            'away_team' => 'Liverpool',
            'starts_at' => Carbon::now()->addDays(3)->setTime(17, 30),
            'status' => 'upcoming',
        ]);

        Event::create([
            'sport_id' => $soccer->id,
            'home_team' => 'Manchester City',
            'away_team' => 'Tottenham Hotspur',
            'starts_at' => Carbon::now()->addDays(6)->setTime(12, 30),
            'status' => 'upcoming',
        ]);

        // Create Tennis Events
        Event::create([
            'sport_id' => $tennis->id,
            'home_team' => 'Novak Djokovic',
            'away_team' => 'Rafael Nadal',
            'starts_at' => Carbon::now()->addDays(2)->setTime(14, 0),
            'status' => 'upcoming',
        ]);

        Event::create([
            'sport_id' => $tennis->id,
            'home_team' => 'Carlos Alcaraz',
            'away_team' => 'Daniil Medvedev',
            'starts_at' => Carbon::now()->addDays(4)->setTime(11, 0),
            'status' => 'upcoming',
        ]);

        $this->command->info('✅ Created 4 sports and 11 events');
    }
}
