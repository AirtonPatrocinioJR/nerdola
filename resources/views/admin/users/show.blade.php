@extends('layouts.app')

@section('title', 'Detalhes do Usuário - Nerdola Bank')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-700">← Voltar</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Informações do Usuário</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600">Nome</p>
                    <p class="font-semibold">{{ $user->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-semibold">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Telefone</p>
                    <p class="font-semibold">{{ $user->phone }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Status</p>
                    <span class="px-2 py-1 text-xs rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $user->is_active ? 'Ativo' : 'Bloqueado' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Carteira</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600">Código</p>
                    <p class="font-semibold">{{ $user->wallet->code ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Saldo</p>
                    <p class="font-semibold text-2xl text-blue-600">{{ number_format($user->wallet->balance ?? 0, 2, ',', '.') }} NDL</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Últimas Transações</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($transactions as $transaction)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">{{ ucfirst($transaction->type) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold">
                                {{ number_format($transaction->amount, 2, ',', '.') }} NDL
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs rounded-full {{ $transaction->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

