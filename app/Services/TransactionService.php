<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use App\Models\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function validateUserCanTransfer(User $user): bool
    {
        return $user->isClient() && $user->is_active;
    }

    public function validateUserCanReceive(User $user): bool
    {
        // Permite recebimento se for cliente ativo ou usuário do sistema
        return ($user->isClient() && $user->is_active) || $user->isSystem();
    }

    public function validateBalance(Wallet $wallet, float $amount): bool
    {
        return $wallet->balance >= $amount;
    }

    public function createDeposit(User $user, float $amount, string $description = null, string $source = 'admin_panel'): Transaction
    {
        if (!$this->validateUserCanReceive($user)) {
            throw new \Exception('Usuário não pode receber depósitos.');
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            throw new \Exception('Carteira não encontrada.');
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
                'description' => $description ?? 'Depósito',
                'source' => $source,
            ]);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar depósito: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createPayment(User $payer, QrCode $qrCode, float $amount = null): Transaction
    {
        if (!$this->validateUserCanTransfer($payer)) {
            throw new \Exception('Usuário não pode realizar pagamentos.');
        }

        $amount = $amount ?? $qrCode->amount;
        if (!$amount) {
            throw new \Exception('Valor não especificado.');
        }

        $payerWallet = $payer->wallet;
        $receiverWallet = $qrCode->wallet;

        if (!$this->validateBalance($payerWallet, $amount)) {
            throw new \Exception('Saldo insuficiente.');
        }

        if (!$qrCode->isValid()) {
            throw new \Exception('QR Code inválido ou expirado.');
        }

        // Validar recebimento apenas se o QR Code tiver um usuário vinculado
        // QR Codes do sistema podem não ter user_id, mas têm wallet_id
        if ($qrCode->user_id && !$this->validateUserCanReceive($qrCode->user)) {
            throw new \Exception('Recebedor não pode receber pagamentos.');
        }
        
        // Se não tem user_id mas tem wallet_id, verificar se a carteira pertence ao sistema
        if (!$qrCode->user_id && $qrCode->wallet_id) {
            $wallet = $qrCode->wallet;
            if ($wallet && $wallet->user && !$wallet->user->isSystem()) {
                throw new \Exception('QR Code inválido.');
            }
        }

        DB::beginTransaction();
        try {
            $payerWallet->subtractBalance($amount);
            $receiverWallet->addBalance($amount);

            // Determinar to_user_id e source
            $toUserId = $qrCode->user_id ?? $receiverWallet->user_id ?? null;
            $source = 'qr_code';
            if ($receiverWallet->user && $receiverWallet->user->isSystem()) {
                $source = 'admin_qr_code';
            }

            $transaction = Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'from_wallet_id' => $payerWallet->id,
                'to_wallet_id' => $receiverWallet->id,
                'from_user_id' => $payer->id,
                'to_user_id' => $toUserId,
                'status' => 'completed',
                'description' => $qrCode->description,
                'source' => $source,
                'qr_code_id' => $qrCode->id,
            ]);

            $qrCode->incrementUsage();

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar pagamento: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createTransfer(User $sender, User $receiver, float $amount, string $description = null): Transaction
    {
        if (!$this->validateUserCanTransfer($sender)) {
            throw new \Exception('Remetente não pode realizar transferências.');
        }

        if (!$this->validateUserCanReceive($receiver)) {
            throw new \Exception('Destinatário não pode receber transferências.');
        }

        if ($receiver->isAdmin()) {
            throw new \Exception('Não é possível transferir para contas de administrador.');
        }

        $senderWallet = $sender->wallet;
        $receiverWallet = $receiver->wallet;

        if (!$this->validateBalance($senderWallet, $amount)) {
            throw new \Exception('Saldo insuficiente.');
        }

        DB::beginTransaction();
        try {
            $senderWallet->subtractBalance($amount);
            $receiverWallet->addBalance($amount);

            $transaction = Transaction::create([
                'type' => 'transfer',
                'amount' => $amount,
                'from_wallet_id' => $senderWallet->id,
                'to_wallet_id' => $receiverWallet->id,
                'from_user_id' => $sender->id,
                'to_user_id' => $receiver->id,
                'status' => 'completed',
                'description' => $description,
                'source' => 'transfer',
            ]);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar transferência: ' . $e->getMessage());
            throw $e;
        }
    }
}

