<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class QrCodeController extends Controller
{
    public function generatePayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        $user = auth()->user();
        $wallet = $user->wallet;

        $qrCode = QrCode::create([
            'type' => 'payment',
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $validated['amount'] ?? null,
            'description' => $validated['description'],
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? 1,
        ]);

        // Usar SVG que não precisa de extensões adicionais
        $qrCodeImage = QrCodeGenerator::format('svg')
            ->size(300)
            ->generate($qrCode->getUrl());

        return view('client.qrcode.show', compact('qrCode', 'qrCodeImage'));
    }

    public function showPaymentForm()
    {
        return view('client.qrcode.generate-payment');
    }

    public function processPayment($token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'payment')
            ->firstOrFail();

        if (!$qrCode->isValid()) {
            abort(404, 'QR Code inválido ou expirado.');
        }

        return view('client.qrcode.confirm-payment', compact('qrCode'));
    }

    public function confirmPayment(Request $request, $token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'payment')
            ->firstOrFail();

        if (!$qrCode->isValid()) {
            return back()->withErrors(['error' => 'QR Code inválido ou expirado.']);
        }

        $validated = $request->validate([
            'amount' => $qrCode->amount 
                ? ['required', 'numeric', function ($attribute, $value, $fail) use ($qrCode) {
                    if ((float)$value !== (float)$qrCode->amount) {
                        $fail('O valor deve ser exatamente ' . number_format($qrCode->amount, 2, ',', '.') . ' NDL.');
                    }
                }]
                : ['required', 'numeric', 'min:0.01'],
        ]);

        $user = auth()->user();
        $wallet = $user->wallet;
        $amount = $qrCode->amount ?? $validated['amount'];

        if (!$wallet->hasBalance($amount)) {
            return back()->withErrors(['amount' => 'Saldo insuficiente.']);
        }

        DB::beginTransaction();
        try {
            $wallet->subtractBalance($amount);
            $qrCode->wallet->addBalance($amount);

            Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $qrCode->wallet_id,
                'from_user_id' => $user->id,
                'to_user_id' => $qrCode->user_id ?? $qrCode->wallet->user_id ?? null,
                'status' => 'completed',
                'description' => $qrCode->description,
                'source' => $qrCode->wallet && $qrCode->wallet->user && $qrCode->wallet->user->isSystem() ? 'admin_qr_code' : 'qr_code',
                'qr_code_id' => $qrCode->id,
            ]);

            $qrCode->incrementUsage();
            DB::commit();

            return redirect()->route('client.dashboard')
                ->with('success', 'Pagamento realizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
        }
    }

    public function apiShowPaymentForm()
    {
        $user = auth()->user();
        return response()->json([
            'wallet' => $user->wallet
        ]);
    }

    public function apiGeneratePayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        $user = auth()->user();
        $wallet = $user->wallet;

        $qrCode = QrCode::create([
            'type' => 'payment',
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $validated['amount'] ?? null,
            'description' => $validated['description'],
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? 1,
        ]);

        // Usar SVG que não precisa de extensões adicionais
        $qrCodeImage = QrCodeGenerator::format('svg')
            ->size(300)
            ->generate($qrCode->getUrl());

        return response()->json([
            'qr_code' => $qrCode,
            'qr_code_image' => 'data:image/svg+xml;base64,' . base64_encode($qrCodeImage),
            'url' => $qrCode->getUrl()
        ]);
    }

    public function apiProcessPayment($token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'payment')
            ->with(['user', 'wallet.user'])
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

    public function apiConfirmPayment(Request $request, $token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('type', 'payment')
            ->with(['wallet', 'user'])
            ->firstOrFail();

        if (!$qrCode->isValid()) {
            return response()->json([
                'message' => 'QR Code inválido ou expirado.'
            ], 422);
        }

        $validated = $request->validate([
            'amount' => $qrCode->amount 
                ? ['required', 'numeric', function ($attribute, $value, $fail) use ($qrCode) {
                    if ((float)$value !== (float)$qrCode->amount) {
                        $fail('O valor deve ser exatamente ' . number_format($qrCode->amount, 2, ',', '.') . ' NDL.');
                    }
                }]
                : ['required', 'numeric', 'min:0.01'],
        ]);

        $user = auth()->user();
        $wallet = $user->wallet;
        $amount = $qrCode->amount ?? $validated['amount'];

        if (!$wallet->hasBalance($amount)) {
            return response()->json([
                'message' => 'Saldo insuficiente.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $wallet->subtractBalance($amount);
            $qrCode->wallet->addBalance($amount);

            $transaction = Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $qrCode->wallet_id,
                'from_user_id' => $user->id,
                'to_user_id' => $qrCode->user_id ?? $qrCode->wallet->user_id ?? null,
                'status' => 'completed',
                'description' => $qrCode->description,
                'source' => $qrCode->wallet && $qrCode->wallet->user && $qrCode->wallet->user->isSystem() ? 'admin_qr_code' : 'qr_code',
                'qr_code_id' => $qrCode->id,
            ]);

            $qrCode->incrementUsage();
            DB::commit();

            return response()->json([
                'message' => 'Pagamento realizado com sucesso!',
                'transaction' => $transaction->load(['fromUser', 'toUser', 'fromWallet', 'toWallet'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
}

