#!/bin/bash

# Script para corrigir problema do Vite em produção
# Execute este script no servidor via SSH

echo "🔍 Diagnosticando problema do Vite em produção..."
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se está no diretório correto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Erro: Execute este script no diretório raiz do projeto Laravel${NC}"
    exit 1
fi

# 1. Verificar APP_ENV no .env
echo "1️⃣ Verificando APP_ENV no .env..."
if [ -f ".env" ]; then
    APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
    if [ "$APP_ENV" != "production" ]; then
        echo -e "${YELLOW}⚠️  APP_ENV está definido como: $APP_ENV${NC}"
        echo -e "${YELLOW}   Deve ser 'production'. Corrigindo...${NC}"
        sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
        echo -e "${GREEN}   ✓ APP_ENV corrigido para 'production'${NC}"
    else
        echo -e "${GREEN}   ✓ APP_ENV já está como 'production'${NC}"
    fi
else
    echo -e "${RED}   ❌ Arquivo .env não encontrado!${NC}"
    exit 1
fi

# 2. Verificar se public/hot existe (indica Vite dev server)
echo ""
echo "2️⃣ Verificando arquivo public/hot..."
if [ -f "public/hot" ]; then
    echo -e "${YELLOW}⚠️  Arquivo public/hot encontrado (indica modo dev)${NC}"
    echo -e "${YELLOW}   Removendo...${NC}"
    rm -f public/hot
    echo -e "${GREEN}   ✓ Arquivo public/hot removido${NC}"
else
    echo -e "${GREEN}   ✓ Arquivo public/hot não existe (correto)${NC}"
fi

# 3. Verificar se manifest.json existe
echo ""
echo "3️⃣ Verificando assets compilados..."
if [ -f "public/build/manifest.json" ]; then
    echo -e "${GREEN}   ✓ public/build/manifest.json encontrado${NC}"
    MANIFEST_SIZE=$(stat -f%z "public/build/manifest.json" 2>/dev/null || stat -c%s "public/build/manifest.json" 2>/dev/null || echo "0")
    if [ "$MANIFEST_SIZE" -gt 0 ]; then
        echo -e "${GREEN}   ✓ Arquivo não está vazio${NC}"
    else
        echo -e "${RED}   ❌ Arquivo está vazio!${NC}"
        echo -e "${YELLOW}   Você precisa compilar os assets localmente e fazer upload${NC}"
    fi
else
    echo -e "${RED}   ❌ public/build/manifest.json NÃO encontrado!${NC}"
    echo -e "${YELLOW}   Você precisa:${NC}"
    echo -e "${YELLOW}   1. Compilar localmente: npm run build${NC}"
    echo -e "${YELLOW}   2. Fazer upload da pasta public/build/ via FTP${NC}"
    exit 1
fi

# 4. Verificar permissões
echo ""
echo "4️⃣ Verificando permissões..."
chmod -R 755 public/build 2>/dev/null
echo -e "${GREEN}   ✓ Permissões de public/build configuradas${NC}"

# 5. Remover arquivos de cache antigos
echo ""
echo "5️⃣ Removendo arquivos de cache antigos..."
rm -f bootstrap/cache/*.php 2>/dev/null
echo -e "${GREEN}   ✓ Arquivos de cache removidos${NC}"

# 6. Limpar todos os caches
echo ""
echo "6️⃣ Limpando caches do Laravel..."
php artisan optimize:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
echo -e "${GREEN}   ✓ Caches limpos${NC}"

# 7. Recriar cache de configuração
echo ""
echo "7️⃣ Recriando cache de configuração..."
php artisan config:cache > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ Cache de configuração recriado${NC}"
else
    echo -e "${RED}   ❌ Erro ao recriar cache de configuração${NC}"
    exit 1
fi

# 8. Verificar se APP_ENV está correto no cache
echo ""
echo "8️⃣ Verificando cache de configuração..."
CACHED_ENV=$(php artisan config:show app.env 2>/dev/null | grep -oP '(?<=env => ).*' | tr -d "'\"")
if [ "$CACHED_ENV" = "production" ]; then
    echo -e "${GREEN}   ✓ APP_ENV no cache: production${NC}"
else
    echo -e "${RED}   ❌ APP_ENV no cache: $CACHED_ENV (deveria ser 'production')${NC}"
    echo -e "${YELLOW}   Tentando corrigir novamente...${NC}"
    php artisan config:clear
    php artisan config:cache
    CACHED_ENV=$(php artisan config:show app.env 2>/dev/null | grep -oP '(?<=env => ).*' | tr -d "'\"")
    if [ "$CACHED_ENV" = "production" ]; then
        echo -e "${GREEN}   ✓ Corrigido!${NC}"
    else
        echo -e "${RED}   ❌ Ainda com problema. Verifique o arquivo .env manualmente${NC}"
    fi
fi

# 9. Recriar outros caches
echo ""
echo "9️⃣ Recriando outros caches..."
php artisan route:cache > /dev/null 2>&1
php artisan view:cache > /dev/null 2>&1
echo -e "${GREEN}   ✓ Caches recriados${NC}"

# 10. Resumo final
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ Correção concluída!${NC}"
echo ""
echo "📋 Próximos passos:"
echo "   1. Limpe o cache do navegador (Ctrl+Shift+R ou Ctrl+F5)"
echo "   2. Acesse: http://nerdola.akaigrupo.com.br/"
echo "   3. Verifique o console do navegador (F12)"
echo ""
echo "🔍 Se o problema persistir:"
echo "   - Verifique se public/build/manifest.json existe e não está vazio"
echo "   - Verifique os logs: tail -f storage/logs/laravel.log"
echo "   - Verifique se APP_ENV=production no .env"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

