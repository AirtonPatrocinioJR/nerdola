@extends('layouts.app')

@section('title', 'Extrato - Nerdola Bank')

@section('content')
<div class="max-w-md mx-auto px-4 py-6 pb-24">
    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <h2 class="text-xl font-bold mb-4">Extrato</h2>
        
        <form method="GET" action="{{ route('client.transactions.index') }}" class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Todos</option>
                    <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Depósito</option>
                    <option value="payment" {{ request('type') == 'payment' ? 'selected' : '' }}>Pagamento</option>
                    <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Transferência</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">De</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Até</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Filtrar</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md">
        @if($transactions->count() > 0)
            <div class="divide-y">
                @foreach($transactions as $transaction)
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-medium">
                                    @if($transaction->to_wallet_id == $wallet->id)
                                        Recebido de {{ $transaction->fromUser->name ?? 'Sistema' }}
                                    @else
                                        Enviado para {{ $transaction->toUser->name ?? 'Sistema' }}
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ ucfirst($transaction->type) }}
                                    @if($transaction->description)
                                        - {{ $transaction->description }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-lg {{ $transaction->to_wallet_id == $wallet->id ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->to_wallet_id == $wallet->id ? '+' : '-' }}{{ number_format($transaction->amount, 2, ',', '.') }} NDL
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-4">
                {{ $transactions->links() }}
            </div>
        @else
            <p class="text-center text-gray-500 py-8">Nenhuma transação encontrada</p>
        @endif
    </div>
</div>
@endsection

