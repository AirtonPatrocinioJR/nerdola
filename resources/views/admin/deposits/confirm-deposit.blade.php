@extends('layouts.app')

@section('title', 'Confirmar Depósito - Nerdola Bank')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-6">Confirmar Depósito via QR Code</h2>

        <div class="space-y-4 mb-6">
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
        </div>

        <form method="POST" action="{{ route('admin.deposits.confirm', $qrCode->token) }}">
            @csrf

            <div class="mb-4">
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Cliente que receberá o depósito
                </label>
                <select id="user_id" name="user_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Selecione um cliente</option>
                    @foreach(\App\Models\User::where('role', 'client')->where('is_active', true)->get() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                    @endforeach
                </select>
            </div>

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
                    Confirmar Depósito
                </button>
                <a href="{{ route('admin.deposits.index') }}"
                   class="block text-center w-full bg-gray-200 text-gray-700 py-3 rounded-md hover:bg-gray-300 font-medium">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

