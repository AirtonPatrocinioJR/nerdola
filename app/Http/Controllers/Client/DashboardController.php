<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $wallet = $user->wallet;
        $transactions = Transaction::where(function ($query) use ($wallet) {
            $query->where('from_wallet_id', $wallet->id)
                  ->orWhere('to_wallet_id', $wallet->id);
        })
        ->where('status', 'completed')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        return view('client.dashboard', compact('wallet', 'transactions'));
    }

    public function apiIndex()
    {
        $user = auth()->user();
        $wallet = $user->wallet;
        $transactions = Transaction::where(function ($query) use ($wallet) {
            $query->where('from_wallet_id', $wallet->id)
                  ->orWhere('to_wallet_id', $wallet->id);
        })
        ->where('status', 'completed')
        ->with(['fromUser', 'toUser', 'fromWallet', 'toWallet'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        return response()->json([
            'wallet' => $wallet,
            'transactions' => $transactions
        ]);
    }
}

