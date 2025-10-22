<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WalletController extends Controller
{
    /**
     * Display the user's wallet page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get or create wallet for user
        $wallet = $user->wallet ?? $this->createWalletForUser($user);

        // Get recent transactions
        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Wallet/Index', [
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Add play money to wallet (for testing/demo purposes).
     */
    public function addPlayMoney(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        $wallet = $request->user()->wallet;

        if (!$wallet) {
            $wallet = $this->createWalletForUser($request->user());
        }

        $wallet->deposit(
            $request->amount,
            'Play money added'
        );

        return redirect()->route('wallet.index')->with('success', 'Play money added successfully!');
    }

    /**
     * Create a wallet for a user with starting balance.
     */
    private function createWalletForUser($user): Wallet
    {
        $wallet = $user->wallet()->create([
            'balance' => 10000.00,
            'currency' => 'USD',
        ]);

        // Record the initial deposit
        $wallet->transactions()->create([
            'type' => 'credit',
            'amount' => 10000.00,
            'balance_after' => 10000.00,
            'description' => 'Starting play money',
        ]);

        return $wallet;
    }
}
