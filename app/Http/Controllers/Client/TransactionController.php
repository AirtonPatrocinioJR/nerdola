<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $wallet = $user->wallet;

        $query = Transaction::where(function ($q) use ($wallet) {
            $q->where('from_wallet_id', $wallet->id)
              ->orWhere('to_wallet_id', $wallet->id);
        })->where('status', 'completed');

        // Filtros
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('client.transactions.index', compact('transactions', 'wallet'));
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $wallet = $user->wallet;

        // Buscar carteira de destino por código, email ou telefone
        $destination = $validated['destination'];
        $destinationWallet = Wallet::where(function($query) use ($destination) {
            $query->where('code', $destination)
                  ->orWhereHas('user', function ($q) use ($destination) {
                      $q->where(function($subQuery) use ($destination) {
                          $subQuery->where('email', $destination)
                                   ->orWhere('phone', $destination);
                      })->where('role', 'client')
                        ->where('is_active', true);
                  });
        })->first();

        if (!$destinationWallet) {
            return back()->withErrors(['destination' => 'Carteira de destino não encontrada.']);
        }

        // Verificar se não é administrador
        if ($destinationWallet->user->isAdmin()) {
            return back()->withErrors(['destination' => 'Não é possível transferir para contas de administrador.']);
        }

        // Verificar saldo
        if (!$wallet->hasBalance($validated['amount'])) {
            return back()->withErrors(['amount' => 'Saldo insuficiente.']);
        }

        // Executar transferência
        DB::beginTransaction();
        try {
            $wallet->subtractBalance($validated['amount']);
            $destinationWallet->addBalance($validated['amount']);

            Transaction::create([
                'type' => 'transfer',
                'amount' => $validated['amount'],
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $destinationWallet->id,
                'from_user_id' => $user->id,
                'to_user_id' => $destinationWallet->user_id,
                'status' => 'completed',
                'description' => $validated['description'],
                'source' => 'transfer',
            ]);

            DB::commit();
            return redirect()->route('client.dashboard')
                ->with('success', 'Transferência realizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao realizar transferência: ' . $e->getMessage()]);
        }
    }

    public function showTransferForm()
    {
        return view('client.transactions.transfer');
    }

    public function apiIndex(Request $request)
    {
        $user = auth()->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => 0
            ]);
        }

        $query = Transaction::where(function ($q) use ($wallet) {
            $q->where('from_wallet_id', $wallet->id)
              ->orWhere('to_wallet_id', $wallet->id);
        })->where('status', 'completed')
          ->with(['fromUser', 'toUser', 'fromWallet', 'toWallet']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($transactions);
    }

    public function apiShowTransferForm()
    {
        $user = auth()->user();
        return response()->json([
            'wallet' => $user->wallet
        ]);
    }

    public function apiTransfer(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $wallet = $user->wallet;

        $destination = $validated['destination'];
        $destinationWallet = Wallet::where(function($query) use ($destination) {
            $query->where('code', $destination)
                  ->orWhereHas('user', function ($q) use ($destination) {
                      $q->where(function($subQuery) use ($destination) {
                          $subQuery->where('email', $destination)
                                   ->orWhere('phone', $destination);
                      })->where('role', 'client')
                        ->where('is_active', true);
                  });
        })->first();

        if (!$destinationWallet) {
            return response()->json([
                'message' => 'Carteira de destino não encontrada.'
            ], 404);
        }

        if ($destinationWallet->user->isAdmin()) {
            return response()->json([
                'message' => 'Não é possível transferir para contas de administrador.'
            ], 422);
        }

        if (!$wallet->hasBalance($validated['amount'])) {
            return response()->json([
                'message' => 'Saldo insuficiente.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $wallet->subtractBalance($validated['amount']);
            $destinationWallet->addBalance($validated['amount']);

            $transaction = Transaction::create([
                'type' => 'transfer',
                'amount' => $validated['amount'],
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $destinationWallet->id,
                'from_user_id' => $user->id,
                'to_user_id' => $destinationWallet->user_id,
                'status' => 'completed',
                'description' => $validated['description'],
                'source' => 'transfer',
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Transferência realizada com sucesso!',
                'transaction' => $transaction->load(['fromUser', 'toUser', 'fromWallet', 'toWallet'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao realizar transferência: ' . $e->getMessage()
            ], 500);
        }
    }
}

