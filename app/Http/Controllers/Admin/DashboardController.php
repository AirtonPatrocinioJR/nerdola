<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::where('role', 'client')->count(),
            'active_users' => User::where('role', 'client')->where('is_active', true)->count(),
            'blocked_users' => User::where('role', 'client')->where('is_active', false)->count(),
            'total_transactions' => Transaction::where('status', 'completed')->count(),
            'total_volume' => Transaction::where('status', 'completed')->sum('amount'),
            'today_transactions' => Transaction::where('status', 'completed')
                ->whereDate('created_at', today())->count(),
            'today_volume' => Transaction::where('status', 'completed')
                ->whereDate('created_at', today())->sum('amount'),
        ];

        $recentTransactions = Transaction::with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentTransactions'));
    }

    public function apiIndex()
    {
        $stats = [
            'total_users' => User::where('role', 'client')->count(),
            'active_users' => User::where('role', 'client')->where('is_active', true)->count(),
            'blocked_users' => User::where('role', 'client')->where('is_active', false)->count(),
            'total_transactions' => Transaction::where('status', 'completed')->count(),
            'total_volume' => Transaction::where('status', 'completed')->sum('amount'),
            'today_transactions' => Transaction::where('status', 'completed')
                ->whereDate('created_at', today())->count(),
            'today_volume' => Transaction::where('status', 'completed')
                ->whereDate('created_at', today())->sum('amount'),
        ];

        $recentTransactions = Transaction::with(['fromUser', 'toUser', 'fromWallet', 'toWallet'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_transactions' => $recentTransactions
        ]);
    }
}

