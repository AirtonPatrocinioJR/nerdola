<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class PaymentController extends Controller
{
    /**
     * Gerar QR Code de pagamento vinculado à carteira do sistema
     */
    public function apiGeneratePaymentQrCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'nullable|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
                'expires_at' => 'nullable|date|after:now',
            ]);

            // Buscar usuário do sistema
            $systemUser = User::where('email', 'system@nerdola.com')->first();
            
            if (!$systemUser) {
                return response()->json([
                    'message' => 'Usuário do sistema não encontrado. Execute o seeder para criar.'
                ], 500);
            }

            $systemWallet = $systemUser->wallet;
            
            if (!$systemWallet) {
                return response()->json([
                    'message' => 'Carteira do sistema não encontrada. Execute o seeder para criar.'
                ], 500);
            }

            // Converter expires_at se fornecido
            $expiresAt = null;
            if (!empty($validated['expires_at'])) {
                $expiresAt = \Carbon\Carbon::parse($validated['expires_at']);
                if ($expiresAt->isPast()) {
                    return response()->json([
                        'message' => 'A data de expiração deve ser futura.'
                    ], 422);
                }
            }

            // Criar QR Code tipo payment vinculado à carteira do sistema
            // usage_limit = null significa ilimitado
            $qrCode = QrCode::create([
                'type' => 'payment',
                'user_id' => $systemUser->id,
                'wallet_id' => $systemWallet->id,
                'amount' => $validated['amount'] ?? null,
                'description' => $validated['description'] ?? null,
                'expires_at' => $expiresAt,
                'usage_limit' => null, // Ilimitado
                'is_active' => true,
            ]);

            $qrCodeUrl = $qrCode->getUrl();
            
            // Gerar imagem SVG do QR Code
            $qrCodeImage = QrCodeGenerator::format('svg')
                ->size(300)
                ->generate($qrCodeUrl);

            return response()->json([
                'qr_code' => $qrCode,
                'qr_code_image' => 'data:image/svg+xml;base64,' . base64_encode($qrCodeImage),
                'url' => $qrCodeUrl
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar QR Code de pagamento: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Erro ao gerar QR Code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar QR Codes de pagamento gerados pelo admin (opcional)
     */
    public function apiIndex(Request $request)
    {
        $systemUser = User::where('email', 'system@nerdola.com')->first();
        
        if (!$systemUser) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => 0
            ]);
        }

        $query = QrCode::where('type', 'payment')
            ->where('user_id', $systemUser->id)
            ->with(['wallet', 'user']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $qrCodes = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($qrCodes);
    }
}

