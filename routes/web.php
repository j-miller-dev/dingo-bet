<?php

use App\Http\Controllers\BetController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\WalletController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Debug route - check events in database (PUBLIC - remove after debugging)
Route::get('/debug/events', function () {
    $events = \App\Models\Event::with('sport')->get();
    $now = now();
    return response()->json([
        'total_events' => $events->count(),
        'now' => $now->toDateTimeString(),
        'events' => $events->map(function($event) use ($now) {
            return [
                'id' => $event->id,
                'sport' => $event->sport->name,
                'matchup' => $event->home_team . ' vs ' . $event->away_team,
                'starts_at' => $event->starts_at,
                'status' => $event->status,
                'is_past' => $event->starts_at < $now,
            ];
        })
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Wallet routes
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/add-play-money', [WalletController::class, 'addPlayMoney'])->name('wallet.add-play-money');

    // Event routes
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

    // Bet routes
    Route::get('/bets', [BetController::class, 'index'])->name('bets.index');
    Route::post('/bets', [BetController::class, 'store'])->name('bets.store');
    Route::post('/bets/{bet}/cancel', [BetController::class, 'cancel'])->name('bets.cancel');

    // Settlement routes (in production, add admin middleware here)
    Route::get('/settlement', [SettlementController::class, 'index'])->name('settlement.index');
    Route::post('/settlement/{event}/settle', [SettlementController::class, 'settle'])->name('settlement.settle');
    Route::post('/settlement/{event}/void', [SettlementController::class, 'void'])->name('settlement.void');
});

require __DIR__.'/auth.php';
