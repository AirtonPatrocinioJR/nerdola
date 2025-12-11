<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg md:hidden">
    <div class="flex justify-around items-center h-16">
        <a href="<?php echo e(route('client.dashboard')); ?>" class="flex flex-col items-center justify-center flex-1 <?php echo e(request()->routeIs('client.dashboard') ? 'text-blue-600' : 'text-gray-600'); ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span class="text-xs mt-1">Início</span>
        </a>
        <a href="<?php echo e(route('client.transactions.index')); ?>" class="flex flex-col items-center justify-center flex-1 <?php echo e(request()->routeIs('client.transactions.*') ? 'text-blue-600' : 'text-gray-600'); ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <span class="text-xs mt-1">Extrato</span>
        </a>
        <a href="<?php echo e(route('client.qrcode.form')); ?>" class="flex flex-col items-center justify-center flex-1 <?php echo e(request()->routeIs('client.qrcode.*') ? 'text-blue-600' : 'text-gray-600'); ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
            </svg>
            <span class="text-xs mt-1">QR Code</span>
        </a>
        <a href="<?php echo e(route('client.transfer.form')); ?>" class="flex flex-col items-center justify-center flex-1 <?php echo e(request()->routeIs('client.transfer.*') ? 'text-blue-600' : 'text-gray-600'); ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
            </svg>
            <span class="text-xs mt-1">Transferir</span>
        </a>
    </div>
</nav>

<div class="h-16 md:hidden"></div>

<?php /**PATH C:\Meus Arquivos\Projetos\nerdola\resources\views/layouts/bottom-nav.blade.php ENDPATH**/ ?>