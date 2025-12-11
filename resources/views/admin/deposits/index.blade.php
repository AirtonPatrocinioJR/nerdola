@extends('layouts.app')

@section('title', 'Depósitos - Nerdola Bank')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Gestão de Depósitos</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <a href="{{ route('admin.deposits.create') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition text-center">
            <div class="text-blue-600 mb-3">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold">Depósito Manual</h3>
            <p class="text-sm text-gray-600 mt-2">Criar depósito diretamente para um cliente</p>
        </a>

        <a href="{{ route('admin.deposits.qrcode.form') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition text-center">
            <div class="text-blue-600 mb-3">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold">Gerar QR Code</h3>
            <p class="text-sm text-gray-600 mt-2">Gerar QR Code de depósito</p>
        </a>
    </div>
</div>
@endsection

