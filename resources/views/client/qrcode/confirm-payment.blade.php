@extends('layouts.app')

@section('title', 'Confirmar Pagamento - Nerdola Bank')

@section('content')
<div class="max-w-md mx-auto px-4 py-6 pb-24">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-6">Confirmar Pagamento</h2>

        <div class="space-y-4 mb-6">
            <div>
                <p class="text-sm text-gray-600">Para</p>
                <p class="font-semibold text-lg">{{ $qrCode->user->name }}</p>
            </div>

            @if($qrCode->amount)
                <div>
                    <p class="text-sm text-gray-600">Valor</p>
                    <p class="font-semibold text-2xl text-blue-600">{{ number_format($qrCode->amount, 2, ',', '.') }} NDL</p>
                </div>
            @endif

            @if($qrCode->description)
                <div>
                    <p class="text-sm text-gray-600">Descrição</p>
                    <p class="font-medium">{{ $qrCode->description }}</p>
                </div>
            @endif

            <div class="p-3 bg-gray-50 rounded-md">
                <p class="text-sm text-gray-600">Seu saldo</p>
                <p class="font-semibold text-lg">{{ number_format(auth()->user()->wallet->balance, 2, ',', '.') }} NDL</p>
            </div>
        </div>

        <form method="POST" action="{{ route('client.qrcode.confirm', $qrCode->token) }}">
            @csrf
            
            @if(!$qrCode->amount)
                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                        Valor (NDL)
                    </label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="0.00">
                </div>
            @endif

            <div class="space-y-2">
                <button type="submit"
                        class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-medium">
                    Confirmar Pagamento
                </button>
                <a href="{{ route('client.dashboard') }}"
                   class="block text-center w-full bg-gray-200 text-gray-700 py-3 rounded-md hover:bg-gray-300 font-medium">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

