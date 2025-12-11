# Solução Completa: Erro Vite em Produção

## Problema

O console do navegador mostra:
```
GET http://[::1]:5173/@vite/client net::ERR_CONNECTION_REFUSED
GET http://[::1]:5173/resources/js/app.js net::ERR_CONNECTION_REFUSED
GET http://[::1]:5173/resources/css/app.css net::ERR_CONNECTION_REFUSED
```

O Laravel está tentando usar o Vite em modo de desenvolvimento em vez dos assets compilados.

## Solução Passo a Passo

### Passo 1: Verificar se os Assets Foram Compilados

**No seu computador local (Windows):**

```bash
cd "C:\Meus Arquivos\Projetos\nerdola"
npm run build
```

Isso criará os arquivos em `public/build/`. Verifique se o arquivo `public/build/manifest.json` foi criado.

### Passo 2: Fazer Upload dos Assets via FTP

Faça upload da pasta `public/build/` completa para o servidor. A estrutura no servidor deve ser:

```
/home1/gab51441/nerdola.akaigrupo.com.br/nerdola/public/build/
├── manifest.json
├── assets/
│   ├── app-*.js
│   ├── app-*.css
│   └── ...
```

### Passo 3: Executar Correção no Servidor

**Conecte-se ao servidor via SSH e execute:**

```bash
cd /home1/gab51441/nerdola.akaigrupo.com.br/nerdola

# 1. Remover arquivo hot se existir (indica modo dev do Vite)
rm -f public/hot

# 2. Verificar e corrigir APP_ENV no .env
nano .env
```

No arquivo `.env`, certifique-se de que tem:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nerdola.akaigrupo.com.br
```

Salve o arquivo (Ctrl+X, depois Y, depois Enter).

```bash
# 3. Remover arquivos de cache antigos
rm -f bootstrap/cache/*.php

# 4. Limpar todos os caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Verificar se os assets existem
ls -la public/build/manifest.json
# Se não existir, você precisa fazer upload (veja Passo 2)

# 6. Configurar permissões
chmod -R 755 public/build

# 7. Recriar cache de configuração
php artisan config:cache

# 8. Verificar se APP_ENV está correto no cache
php artisan config:show app.env
# Deve mostrar: "production"

# 9. Recriar outros caches
php artisan route:cache
php artisan view:cache
```

### Passo 4: Verificar

1. **Limpe o cache do navegador** (Ctrl+Shift+R ou Ctrl+F5)
2. **Acesse:** `http://nerdola.akaigrupo.com.br/`
3. **Verifique o console do navegador** (F12) - não deve mais ter erros

## Script Automatizado (Recomendado)

Para facilitar, use o script `fix-vite-production.sh`:

### 1. Fazer upload do script para o servidor

Faça upload do arquivo `fix-vite-production.sh` para o diretório raiz do projeto no servidor.

### 2. Executar no servidor

```bash
cd /home1/gab51441/nerdola.akaigrupo.com.br/nerdola
chmod +x fix-vite-production.sh
./fix-vite-production.sh
```

O script irá:
- ✅ Verificar e corrigir APP_ENV no .env
- ✅ Remover arquivo public/hot se existir
- ✅ Verificar se os assets compilados existem
- ✅ Limpar todos os caches
- ✅ Recriar cache de configuração
- ✅ Verificar se tudo está correto

## Comandos Rápidos (Copiar e Colar)

```bash
cd /home1/gab51441/nerdola.akaigrupo.com.br/nerdola
rm -f public/hot bootstrap/cache/*.php
php artisan optimize:clear
php artisan config:clear
# Edite .env: APP_ENV=production
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 755 public/build
php artisan config:show app.env
```

## Checklist

- [ ] Assets compilados localmente (`npm run build`)
- [ ] Pasta `public/build/` enviada via FTP
- [ ] Arquivo `public/hot` removido no servidor (se existir)
- [ ] `APP_ENV=production` no `.env` do servidor
- [ ] `APP_DEBUG=false` no `.env` do servidor
- [ ] Arquivos de cache removidos (`bootstrap/cache/*.php`)
- [ ] Caches do Laravel limpos
- [ ] Cache de configuração recriado
- [ ] Permissões de `public/build/` configuradas (755)
- [ ] Cache do navegador limpo

## Por Que Isso Acontece?

O Laravel Vite plugin detecta automaticamente se deve usar:
- **Modo Dev**: Se `APP_ENV != 'production'` OU se o arquivo `public/hot` existe
- **Modo Produção**: Se `APP_ENV = 'production'` E o arquivo `public/hot` NÃO existe E `public/build/manifest.json` existe

## Se o Problema Persistir

1. **Verifique os logs:**
```bash
tail -f storage/logs/laravel.log
```

2. **Verifique se o manifest.json existe e não está vazio:**
```bash
ls -lh public/build/manifest.json
cat public/build/manifest.json | head -20
```

3. **Verifique o conteúdo do .env:**
```bash
grep APP_ENV .env
grep APP_DEBUG .env
```

4. **Teste temporariamente com debug:**
```bash
# No .env, defina temporariamente:
APP_DEBUG=true
# Recrie o cache:
php artisan config:clear && php artisan config:cache
# Teste e veja os erros detalhados
# LEMBRE-SE: Volte APP_DEBUG=false depois!
```

## Notas Importantes

1. **Nunca deixe `APP_DEBUG=true` em produção** após resolver o problema
2. **Sempre compile os assets localmente** antes de fazer deploy
3. **Nunca envie `bootstrap/cache/*.php`** via FTP - eles contêm caminhos do sistema
4. **Sempre gere o cache no servidor**, nunca no Windows

