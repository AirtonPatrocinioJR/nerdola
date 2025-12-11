<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class DepositController extends Controller
{
    public function index()
    {
        $deposits = Transaction::where('type', 'deposit')
            ->with(['toUser', 'toWallet'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.deposits.index', compact('deposits'));
    }

    public function create()
    {
        $users = User::where('role', 'client')->where('is_active', true)->get();
        return view('admin.deposits.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->role !== 'client') {
            return back()->withErrors(['user_id' => 'Apenas clientes podem receber depósitos.']);
        }

        $wallet = $user->wallet;

        DB::beginTransaction();
        try {
            $wallet->addBalance($validated['amount']);

            Transaction::create([
                'type' => 'deposit',
                'amount' => $validated['amount'],
                'to_wallet_id' => $wallet->id,
                'to_user_id' => $user->id,
                'status' => 'completed',
                'description' => $validated['description'] ?? 'Depósito administrativo',
                'source' => 'admin_panel',
            ]);

            DB::commit();
            return redirect()->route('admin.deposits.index')
                ->with('success', 'Depósito realizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao realizar depósito: ' . $e->getMessage()]);
        }
    }

    public function generateQrCode(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        $qrCode = QrCode::create([
            'type' => 'deposit',
            'amount' => $validated['amount'] ?? null,
            'description' => $validated['description'],
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? 1,
        ]);

        // Usar SVG que não precisa de extensões adicionais
        $qrCodeImage = QrCodeGenerator::format('svg')
            ->size(300)
            ->generate($qrCode->getUrl());

        return view('admin.deposits.qrcode', compact('qrCode', 'qrCodeImage'));
    }

    public function showQrCodeForm()
    {
        return view('admin.deposits.generate-qrcode');
    }

    public function processDeposit($token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'deposit')
            ->firstOrFail();

        if (!$qrCode->isValid()) {
            abort(404, 'QR Code inválido ou expirado.');
        }

        return view('admin.deposits.confirm-deposit', compact('qrCode'));
    }

    public function apiProcessDeposit($token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'deposit')
            ->firstOrFail();

        if (!$qrCode->isValid()) {
            return response()->json([
                'message' => 'QR Code inválido ou expirado.'
            ], 404);
        }

        return response()->json([
            'qr_code' => $qrCode
        ]);
    }

    public function confirmDeposit(Request $request, $token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'deposit')
            ->firstOrFail();

        if (!$qrCode->isValid()) {
            return back()->withErrors(['error' => 'QR Code inválido ou expirado.']);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => $qrCode->amount 
                ? ['required', 'numeric', function ($attribute, $value, $fail) use ($qrCode) {
                    if ((float)$value !== (float)$qrCode->amount) {
                        $fail('O valor deve ser exatamente ' . number_format($qrCode->amount, 2, ',', '.') . ' NDL.');
                    }
                }]
                : ['required', 'numeric', 'min:0.01'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->role !== 'client') {
            return back()->withErrors(['user_id' => 'Apenas clientes podem receber depósitos.']);
        }

        $wallet = $user->wallet;
        $amount = $qrCode->amount ?? $validated['amount'];

        // Verificar se este cliente já usou este QR Code de depósito
        $existingTransaction = Transaction::where('qr_code_id', $qrCode->id)
            ->where('to_user_id', $user->id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->first();

        if ($existingTransaction) {
            return back()->withErrors(['error' => 'Este cliente já recebeu este depósito anteriormente. Cada QR Code de depósito só pode ser usado uma vez por cliente.']);
        }

        DB::beginTransaction();
        try {
            $wallet->addBalance($amount);

            Transaction::create([
                'type' => 'deposit',
                'amount' => $amount,
                'to_wallet_id' => $wallet->id,
                'to_user_id' => $user->id,
                'status' => 'completed',
                'description' => $qrCode->description ?? 'Depósito via QR Code',
                'source' => 'qr_code',
                'qr_code_id' => $qrCode->id,
            ]);

            $qrCode->incrementUsage();
            DB::commit();

            return redirect()->route('admin.deposits.index')
                ->with('success', 'Depósito realizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao processar depósito: ' . $e->getMessage()]);
        }
    }

    public function apiIndex()
    {
        $deposits = Transaction::where('type', 'deposit')
            ->with(['toUser', 'toWallet'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($deposits);
    }

    public function apiCreate()
    {
        $users = User::where('role', 'client')->where('is_active', true)->get(['id', 'name', 'email']);
        return response()->json(['users' => $users]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Apenas clientes podem receber depósitos.'
            ], 422);
        }

        $wallet = $user->wallet;

        DB::beginTransaction();
        try {
            $wallet->addBalance($validated['amount']);

            $transaction = Transaction::create([
                'type' => 'deposit',
                'amount' => $validated['amount'],
                'to_wallet_id' => $wallet->id,
                'to_user_id' => $user->id,
                'status' => 'completed',
                'description' => $validated['description'] ?? 'Depósito administrativo',
                'source' => 'admin_panel',
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Depósito realizado com sucesso!',
                'transaction' => $transaction->load(['toUser', 'toWallet'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao realizar depósito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShowQrCodeForm()
    {
        return response()->json(['message' => 'OK']);
    }

    public function apiGenerateQrCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'nullable|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
                'expires_at' => 'nullable|date',
                'usage_limit' => 'nullable|integer|min:1',
            ]);

            // Converter expires_at se fornecido
            $expiresAt = null;
            if (!empty($validated['expires_at'])) {
                // Converter formato datetime-local para formato do banco
                $expiresAt = \Carbon\Carbon::parse($validated['expires_at']);
                // Validar se a data é futura
                if ($expiresAt->isPast()) {
                    return response()->json([
                        'message' => 'A data de expiração deve ser futura.'
                    ], 422);
                }
            }

            $qrCode = QrCode::create([
                'type' => 'deposit',
                'amount' => $validated['amount'] ?? null,
                'description' => $validated['description'] ?? null,
                'expires_at' => $expiresAt,
                'usage_limit' => $validated['usage_limit'] ?? 1,
                'is_active' => true,
            ]);

            $qrCodeUrl = $qrCode->getUrl();
            
            // Usar SVG que não precisa de extensões adicionais
            $qrCodeImage = QrCodeGenerator::format('svg')
                ->size(300)
                ->generate($qrCodeUrl);

            return response()->json([
                'qr_code' => $qrCode,
                'qr_code_image' => 'data:image/svg+xml;base64,' . base64_encode($qrCodeImage),
                'url' => $qrCodeUrl
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar QR Code de depósito: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Erro ao gerar QR Code: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiConfirmDeposit(Request $request, $token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'deposit')
            ->firstOrFail();

        if (!$qrCode->isValid()) {
            return response()->json([
                'message' => 'QR Code inválido ou expirado.'
            ], 422);
        }

        $authUser = auth()->user();
        
        // Se for cliente, usar o próprio ID. Se for admin, validar user_id
        if ($authUser->role === 'client') {
            $validated = $request->validate([
                'amount' => $qrCode->amount 
                    ? ['required', 'numeric', function ($attribute, $value, $fail) use ($qrCode) {
                        if ((float)$value !== (float)$qrCode->amount) {
                            $fail('O valor deve ser exatamente ' . number_format($qrCode->amount, 2, ',', '.') . ' NDL.');
                        }
                    }]
                    : ['required', 'numeric', 'min:0.01'],
            ]);
            $user = $authUser;
        } else {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'amount' => $qrCode->amount 
                    ? ['required', 'numeric', function ($attribute, $value, $fail) use ($qrCode) {
                        if ((float)$value !== (float)$qrCode->amount) {
                            $fail('O valor deve ser exatamente ' . number_format($qrCode->amount, 2, ',', '.') . ' NDL.');
                        }
                    }]
                    : ['required', 'numeric', 'min:0.01'],
            ]);
            $user = User::findOrFail($validated['user_id']);

            if ($user->role !== 'client') {
                return response()->json([
                    'message' => 'Apenas clientes podem receber depósitos.'
                ], 422);
            }
        }

        $wallet = $user->wallet;
        $amount = $qrCode->amount ?? $validated['amount'];

        // Verificar se este cliente já usou este QR Code de depósito
        $existingTransaction = Transaction::where('qr_code_id', $qrCode->id)
            ->where('to_user_id', $user->id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->first();

        if ($existingTransaction) {
            return response()->json([
                'message' => 'Você já recebeu este depósito anteriormente. Cada QR Code de depósito só pode ser usado uma vez por cliente.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $wallet->addBalance($amount);

            $transaction = Transaction::create([
                'type' => 'deposit',
                'amount' => $amount,
                'to_wallet_id' => $wallet->id,
                'to_user_id' => $user->id,
                'status' => 'completed',
                'description' => $qrCode->description ?? 'Depósito via QR Code',
                'source' => 'qr_code',
                'qr_code_id' => $qrCode->id,
            ]);

            $qrCode->incrementUsage();
            DB::commit();

            return response()->json([
                'message' => 'Depósito realizado com sucesso!',
                'transaction' => $transaction->load(['toUser', 'toWallet'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao processar depósito: ' . $e->getMessage()
            ], 500);
        }
    }
}

