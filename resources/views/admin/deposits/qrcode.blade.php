@extends('layouts.app')

@section('title', 'QR Code de Depósito - Nerdola Bank')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <h2 class="text-xl font-bold mb-4">QR Code de Depósito</h2>
        
        <div class="bg-white p-4 rounded-lg inline-block mb-4">
            <img src="data:image/png;base64,{{ base64_encode($qrCodeImage) }}" alt="QR Code" class="mx-auto">
        </div>

        @if($qrCode->amount)
            <p class="text-lg font-semibold mb-2">Valor: {{ number_format($qrCode->amount, 2, ',', '.') }} NDL</p>
        @else
            <p class="text-lg font-semibold mb-2">Valor: Aberto</p>
        @endif

        @if($qrCode->description)
            <p class="text-gray-600 mb-2">{{ $qrCode->description }}</p>
        @endif

        @if($qrCode->expires_at)
            <p class="text-sm text-gray-500 mb-4">Expira em: {{ $qrCode->expires_at->format('d/m/Y H:i') }}</p>
        @endif

        <div class="mt-4 p-3 bg-gray-50 rounded-md">
            <p class="text-xs text-gray-600 mb-1">Link:</p>
            <p class="text-xs break-all">{{ $qrCode->getUrl() }}</p>
        </div>

        <a href="{{ route('admin.deposits.index') }}" class="block mt-6 text-blue-600 hover:text-blue-700">
            Voltar
        </a>
    </div>
</div>
@endsection

