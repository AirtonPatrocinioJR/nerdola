@extends('layouts.app')

@section('title', 'Criar Depósito - Nerdola Bank')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.deposits.index') }}" class="text-blue-600 hover:text-blue-700">← Voltar</a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-6">Criar Depósito</h2>

        <form method="POST" action="{{ route('admin.deposits.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Cliente
                </label>
                <select id="user_id" name="user_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Selecione um cliente</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                    @endforeach
                </select>
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
                       placeholder="Ex: Depósito inicial">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-medium">
                Criar Depósito
            </button>
        </form>
    </div>
</div>
@endsection

