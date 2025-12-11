#!/bin/bash

# Script de Deploy via FTP - Nerdola Bank
# Execute este script no servidor APÓS fazer upload dos arquivos via FTP

echo "🚀 Iniciando deploy do Nerdola Bank..."

# Verificar se está no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do projeto Laravel"
    exit 1
fi

# Deletar arquivos de cache que podem conter caminhos do Windows
echo "🗑️  Removendo arquivos de cache antigos..."
rm -f bootstrap/cache/*.php 2>/dev/null || true

# Limpar todos os caches
echo "🧹 Limpando cache do Laravel..."
php artisan optimize:clear

# Instalar/Atualizar dependências PHP (sem dev dependencies)
echo "📦 Instalando dependências PHP..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
else
    echo "⚠️  Composer não encontrado. Pulando instalação de dependências."
    echo "   Certifique-se de que o diretório vendor/ foi enviado via FTP ou instale o Composer."
fi

# Verificar se .env existe
if [ ! -f ".env" ]; then
    echo "⚠️  Arquivo .env não encontrado!"
    echo "   Crie o arquivo .env baseado no .env.example e configure as variáveis."
    echo "   Execute: cp .env.example .env"
    echo "   Depois: php artisan key:generate"
    exit 1
fi

# Verificar se APP_KEY está configurada
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate
fi

# Verificar e corrigir APP_ENV
echo "🔍 Verificando configuração de ambiente..."
if ! grep -q "APP_ENV=production" .env 2>/dev/null; then
    echo "⚠️  APP_ENV não está configurado como 'production'"
    echo "   Por favor, edite o arquivo .env e defina: APP_ENV=production"
    echo "   Isso é necessário para que o Vite use os assets compilados."
fi

# Verificar se public/build/manifest.json existe
if [ ! -f "public/build/manifest.json" ]; then
    echo "⚠️  AVISO: public/build/manifest.json não encontrado!"
    echo "   Os assets precisam ser compilados localmente e enviados via FTP."
    echo "   Execute localmente: npm run build"
    echo "   Depois faça upload da pasta public/build/ via FTP"
else
    echo "   ✓ Assets compilados encontrados (public/build/manifest.json)"
fi

# Criar diretórios de storage se não existirem
echo "📁 Criando diretórios de storage..."
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache
mkdir -p storage/framework/views
mkdir -p storage/logs
echo "   ✓ Diretórios de storage criados"

# Configurar permissões
echo "🔐 Configurando permissões..."
if [ -d "storage" ]; then
    chmod -R 775 storage
    echo "   ✓ Permissões de storage configuradas"
fi

if [ -d "bootstrap/cache" ]; then
    chmod -R 775 bootstrap/cache
    echo "   ✓ Permissões de bootstrap/cache configuradas"
fi

# Executar migrations (apenas se houver novas)
echo "🗄️  Verificando migrations..."
php artisan migrate --force --no-interaction

# Otimizar para produção
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar se o cache de configuração foi criado corretamente
echo "🔍 Verificando cache de configuração..."
if php artisan config:show app.env 2>/dev/null | grep -q "production"; then
    echo "   ✓ Ambiente configurado como 'production'"
else
    echo "   ⚠️  AVISO: Ambiente pode não estar configurado corretamente"
    echo "   Verifique o arquivo .env e certifique-se de que APP_ENV=production"
fi

echo ""
echo "✅ Deploy concluído com sucesso!"
echo ""
echo "📋 Próximos passos:"
echo "   1. Verifique se o Document Root aponta para o diretório public/"
echo "   2. Teste a aplicação acessando a URL"
echo "   3. Verifique os logs em storage/logs/laravel.log se houver problemas"
echo ""

