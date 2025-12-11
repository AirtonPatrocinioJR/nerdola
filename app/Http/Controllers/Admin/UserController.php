<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Exports\UsersExport;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'client')->with('wallet');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'blocked') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load('wallet', 'sentTransactions', 'receivedTransactions');
        
        $transactions = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.users.show', compact('user', 'transactions'));
    }

    public function block(User $user)
    {
        $user->update(['is_active' => false]);

        return back()->with('success', 'Usuário bloqueado com sucesso.');
    }

    public function unblock(User $user)
    {
        $user->update(['is_active' => true]);

        return back()->with('success', 'Usuário desbloqueado com sucesso.');
    }

    public function apiIndex(Request $request)
    {
        $query = User::where('role', 'client')->with('wallet');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'blocked') {
                $query->where('is_active', false);
            }
        }

        $perPage = $request->get('per_page', 20);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    public function apiShow(User $user)
    {
        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        $user->load('wallet', 'sentTransactions', 'receivedTransactions');
        
        $transactions = $user->transactions()
            ->with(['fromUser', 'toUser', 'fromWallet', 'toWallet'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'user' => $user,
            'transactions' => $transactions
        ]);
    }

    public function apiBlock(User $user)
    {
        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Apenas clientes podem ser bloqueados.'
            ], 422);
        }

        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'Usuário bloqueado com sucesso.',
            'user' => $user->fresh()
        ]);
    }

    public function apiUnblock(User $user)
    {
        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Apenas clientes podem ser desbloqueados.'
            ], 422);
        }

        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'Usuário desbloqueado com sucesso.',
            'user' => $user->fresh()
        ]);
    }

    public function apiList()
    {
        $users = User::where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function export(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status')
        ];

        $filename = 'usuarios_' . date('Y-m-d_His') . '.xlsx';

        $export = new UsersExport($filters);
        $spreadsheet = $export->export();

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function apiExport(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status')
        ];

        $filename = 'usuarios_' . date('Y-m-d_His') . '.xlsx';

        $export = new UsersExport($filters);
        $spreadsheet = $export->export();

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}

