@extends('layouts.app')

@section('title', 'Gerar QR Code - Nerdola Bank')

@section('content')
<div class="max-w-md mx-auto px-4 py-6 pb-24">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-6">Gerar QR Code de Recebimento</h2>

        <form method="POST" action="{{ route('client.qrcode.generate') }}" class="space-y-4">
            @csrf
            
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Valor (NDL) - Deixe em branco para valor aberto
                </label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01"
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

            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">
                    Data de expiração (opcional)
                </label>
                <input type="datetime-local" id="expires_at" name="expires_at"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-1">
                    Limite de uso (padrão: 1)
                </label>
                <input type="number" id="usage_limit" name="usage_limit" min="1" value="1"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-medium">
                Gerar QR Code
            </button>
        </form>
    </div>
</div>
@endsection

