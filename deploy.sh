#!/bin/bash

# Script de deploy para o servidor de produção
# Execute este script após fazer upload dos arquivos para o servidor

echo "🚀 Iniciando deploy..."

# Limpar cache do Laravel
echo "🧹 Limpando cache do Laravel..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Regenerar autoload do Composer (sem dev dependencies)
echo "📦 Regenerando autoload..."
composer install --no-dev --optimize-autoloader

echo "✅ Deploy concluído!"

