<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['fromUser', 'toUser', 'fromWallet', 'toWallet']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_user_id', $request->user_id)
                  ->orWhere('to_user_id', $request->user_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        $users = \App\Models\User::where('role', 'client')->orderBy('name')->get();

        return view('admin.transactions.index', compact('transactions', 'users'));
    }

    public function apiIndex(Request $request)
    {
        $query = Transaction::with(['fromUser', 'toUser', 'fromWallet', 'toWallet']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_user_id', $request->user_id)
                  ->orWhere('to_user_id', $request->user_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->get('per_page', 50);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Carregar usuários apenas na primeira página ou quando necessário
        $users = [];
        if ($request->get('page', 1) == 1 || $request->has('load_users')) {
            $users = \App\Models\User::where('role', 'client')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ];
                });
        }

        return response()->json([
            'transactions' => $transactions,
            'users' => $users
        ]);
    }
}

