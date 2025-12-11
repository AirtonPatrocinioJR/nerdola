<nav class="bg-blue-600 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="<?php echo e(auth()->user()->isAdmin() ? route('admin.dashboard') : route('client.dashboard')); ?>" class="text-xl font-bold">
                    Nerdola Bank
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm"><?php echo e(auth()->user()->name); ?></span>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="bg-blue-700 hover:bg-blue-800 px-4 py-2 rounded text-sm">
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<?php /**PATH C:\Meus Arquivos\Projetos\nerdola\resources\views/layouts/navbar.blade.php ENDPATH**/ ?>