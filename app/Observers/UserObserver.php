<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Automatically create a wallet with starting balance when user registers.
     */
    public function created(User $user): void
    {
        // Create wallet with $10,000 starting balance
        $wallet = $user->wallet()->create([
            'balance' => 10000.00,
            'currency' => 'USD',
        ]);

        // Record the initial transaction
        $wallet->transactions()->create([
            'type' => 'credit',
            'amount' => 10000.00,
            'balance_after' => 10000.00,
            'description' => 'Welcome bonus - Starting play money',
        ]);
    }
}
