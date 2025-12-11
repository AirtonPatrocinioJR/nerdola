

<?php $__env->startSection('title', 'Dashboard Admin - Nerdola Bank'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Dashboard Administrativo</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total de Usuários</p>
            <p class="text-3xl font-bold text-blue-600"><?php echo e($stats['total_users']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Usuários Ativos</p>
            <p class="text-3xl font-bold text-green-600"><?php echo e($stats['active_users']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Volume Total</p>
            <p class="text-3xl font-bold text-purple-600"><?php echo e(number_format($stats['total_volume'], 2, ',', '.')); ?> NDL</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Transações Hoje</p>
            <p class="text-3xl font-bold text-orange-600"><?php echo e($stats['today_transactions']); ?></p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Transações Recentes</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">De</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Para</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $__currentLoopData = $recentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                <?php echo e($transaction->created_at->format('d/m/Y H:i')); ?>

                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    <?php echo e(ucfirst($transaction->type)); ?>

                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                <?php echo e($transaction->fromUser->name ?? 'Sistema'); ?>

                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                <?php echo e($transaction->toUser->name ?? 'Sistema'); ?>

                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold">
                                <?php echo e(number_format($transaction->amount, 2, ',', '.')); ?> NDL
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo e($transaction->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                    <?php echo e(ucfirst($transaction->status)); ?>

                                </span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Meus Arquivos\Projetos\nerdola\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>