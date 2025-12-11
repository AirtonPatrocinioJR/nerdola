@extends('layouts.app')

@section('title', 'Transferir - Nerdola Bank')

@section('content')
<div class="max-w-md mx-auto px-4 py-6 pb-24">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-6">Transferir Nerdolas</h2>
        
        <div class="mb-4 p-3 bg-gray-50 rounded-md">
            <p class="text-sm text-gray-600">Saldo disponível</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format(auth()->user()->wallet->balance, 2, ',', '.') }} NDL</p>
        </div>

        <form method="POST" action="{{ route('client.transfer') }}" class="space-y-4">
            @csrf
            
            <div>
                <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">
                    Carteira de destino
                </label>
                <input type="text" id="destination" name="destination" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Código NDL, e-mail ou telefone">
                <p class="text-xs text-gray-500 mt-1">Digite o código da carteira, e-mail ou telefone</p>
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Valor (NDL)
                </label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="0.00">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Descrição (opcional)
                </label>
                <input type="text" id="description" name="description"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Ex: Pagamento de almoço">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-medium">
                Transferir
            </button>
        </form>
    </div>
</div>
@endsection

