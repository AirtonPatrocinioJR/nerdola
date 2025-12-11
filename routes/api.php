<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Client\DashboardController as ClientDashboard;
use App\Http\Controllers\Client\TransactionController as ClientTransaction;
use App\Http\Controllers\Client\QrCodeController as ClientQrCode;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUser;
use App\Http\Controllers\Admin\DepositController as AdminDeposit;
use App\Http\Controllers\Admin\TransactionController as AdminTransaction;
use App\Http\Controllers\Admin\PaymentController as AdminPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rotas públicas de autenticação
Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/register', [AuthController::class, 'apiRegister']);

// Rotas QR Code públicas (visualização - sem autenticação)
Route::get('/qr/pay/{token}', [ClientQrCode::class, 'apiProcessPayment']);
Route::get('/qr/deposit/{token}', [AdminDeposit::class, 'apiProcessDeposit']);

// Rotas autenticadas
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        // Garantir que o role está incluído na resposta
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);
    });
    
    Route::post('/logout', [AuthController::class, 'apiLogout']);

    // Rotas do Cliente
    Route::middleware(['active', 'client'])->prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', [ClientDashboard::class, 'apiIndex']);
        
        // Transações
        Route::get('/transactions', [ClientTransaction::class, 'apiIndex']);
        Route::get('/transfer', [ClientTransaction::class, 'apiShowTransferForm']);
        Route::post('/transfer', [ClientTransaction::class, 'apiTransfer']);
        
        // QR Code
        Route::get('/qrcode/generate', [ClientQrCode::class, 'apiShowPaymentForm']);
        Route::post('/qrcode/generate', [ClientQrCode::class, 'apiGeneratePayment']);
    });

    // Rotas do Admin
    Route::middleware(['active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'apiIndex']);
        
        // Usuários
        Route::get('/users', [AdminUser::class, 'apiIndex']);
        Route::get('/users/list', [AdminUser::class, 'apiList']); // Lista simples para selects
        Route::get('/users/export', [AdminUser::class, 'apiExport']); // Exportar para Excel
        Route::get('/users/{user}', [AdminUser::class, 'apiShow']);
        Route::post('/users/{user}/block', [AdminUser::class, 'apiBlock']);
        Route::post('/users/{user}/unblock', [AdminUser::class, 'apiUnblock']);
        
        // Depósitos
        Route::get('/deposits', [AdminDeposit::class, 'apiIndex']);
        Route::get('/deposits/create', [AdminDeposit::class, 'apiCreate']);
        Route::post('/deposits', [AdminDeposit::class, 'apiStore']);
        Route::get('/deposits/qrcode', [AdminDeposit::class, 'apiShowQrCodeForm']);
        Route::post('/deposits/qrcode', [AdminDeposit::class, 'apiGenerateQrCode']);
        
        // Transações
        Route::get('/transactions', [AdminTransaction::class, 'apiIndex']);
        
        // Pagamentos (QR Codes gerados pelo admin)
        Route::get('/payments/qrcode', [AdminPayment::class, 'apiIndex']);
        Route::post('/payments/qrcode', [AdminPayment::class, 'apiGeneratePaymentQrCode']);
    });

    // Confirmações de QR Code (requerem autenticação)
    Route::post('/qr/pay/{token}/confirm', [ClientQrCode::class, 'apiConfirmPayment']);
    Route::post('/qr/deposit/{token}/confirm', [AdminDeposit::class, 'apiConfirmDeposit']);
});
