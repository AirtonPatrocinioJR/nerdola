@extends('layouts.app')

@section('title', 'Dashboard - Nerdola Bank')

@section('content')
<div class="max-w-md mx-auto px-4 py-6 pb-24">
    <div class="bg-blue-600 rounded-lg p-6 mb-6 text-white shadow-lg">
        <p class="text-sm opacity-90 mb-1">Saldo disponível</p>
        <p class="text-3xl font-bold">{{ number_format($wallet->balance, 2, ',', '.') }} NDL</p>
        <p class="text-sm opacity-75 mt-2">Carteira: {{ $wallet->code }}</p>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <a href="{{ route('client.qrcode.form') }}" class="bg-white rounded-lg p-4 shadow-md text-center hover:shadow-lg transition">
            <div class="text-blue-600 mb-2">
                <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Receber</p>
        </a>
        <a href="{{ route('client.transfer.form') }}" class="bg-white rounded-lg p-4 shadow-md text-center hover:shadow-lg transition">
            <div class="text-blue-600 mb-2">
                <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Transferir</p>
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold mb-4">Últimas transações</h3>
        @if($transactions->count() > 0)
            <div class="space-y-3">
                @foreach($transactions as $transaction)
                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                        <div>
                            <p class="font-medium text-sm">
                                @if($transaction->to_wallet_id == $wallet->id)
                                    Recebido de {{ $transaction->fromUser->name ?? 'Sistema' }}
                                @else
                                    Enviado para {{ $transaction->toUser->name ?? 'Sistema' }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold {{ $transaction->to_wallet_id == $wallet->id ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->to_wallet_id == $wallet->id ? '+' : '-' }}{{ number_format($transaction->amount, 2, ',', '.') }} NDL
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('client.transactions.index') }}" class="block text-center mt-4 text-blue-600 text-sm">Ver todas</a>
        @else
            <p class="text-gray-500 text-center py-4">Nenhuma transação ainda</p>
        @endif
    </div>
</div>
@endsection

