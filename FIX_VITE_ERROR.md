# Correção: Erro Vite Dev Server em Produção

## Problema

Ao acessar `http://nerdola.akaigrupo.com.br/`, a página fica em branco e o console do navegador mostra:

```
GET http://[::1]:5173/@vite/client net::ERR_CONNECTION_REFUSED
GET http://[::1]:5173/resources/css/app.css net::ERR_CONNECTION_REFUSED
GET http://[::1]:5173/resources/js/app.js net::ERR_CONNECTION_REFUSED
```

## Causa

O Laravel está tentando usar o Vite em modo de desenvolvimento em vez dos assets compilados. Isso acontece quando:

1. O `APP_ENV` no servidor não está configurado como `production`
2. O cache de configuração está desatualizado
3. Os assets compilados não foram enviados ao servidor

## Solução Rápida

Execute os seguintes comandos **no servidor via SSH**:

```bash
# 1. Navegar para o diretório do projeto
cd /caminho/para/seu/projeto

# 2. Limpar todos os caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# 3. Verificar/Editar o arquivo .env
nano .env
# OU
vi .env
```

No arquivo `.env`, certifique-se de que tem:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nerdola.akaigrupo.com.br
```

Salve o arquivo (Ctrl+X, depois Y, depois Enter no nano).

```bash
# 4. Recriar cache de configuração
php artisan config:cache

# 5. Verificar se foi aplicado corretamente
php artisan config:show app.env
# Deve mostrar: "production"

# 6. Verificar se os assets compilados existem
ls -la public/build/manifest.json
# Se não existir, você precisa compilar e enviar os assets (veja abaixo)
```

## Se os Assets Não Foram Compilados

Se o arquivo `public/build/manifest.json` não existe no servidor:

### No seu computador local:

```bash
# 1. Navegar para o projeto
cd "C:\Meus Arquivos\Projetos\nerdola"

# 2. Compilar os assets para produção
npm run build

# 3. Verificar se foi criado
dir public\build\manifest.json
```

### Depois, via FTP:

1. Faça upload da pasta `public/build/` completa para o servidor
2. Certifique-se de que a estrutura no servidor seja: `public/build/manifest.json`

### Depois do upload, no servidor:

```bash
# Verificar permissões
chmod -R 755 public/build

# Limpar cache novamente
php artisan config:clear
php artisan config:cache
```

## Verificação Final

1. **Limpe o cache do navegador** (Ctrl+Shift+R ou Ctrl+F5)
2. **Acesse a URL**: `http://nerdola.akaigrupo.com.br/`
3. **Verifique o console do navegador** (F12) - não deve mais ter erros do Vite
4. **Verifique se a página carrega** corretamente

## Checklist

- [ ] `APP_ENV=production` no `.env` do servidor
- [ ] `APP_DEBUG=false` no `.env` do servidor
- [ ] `public/build/manifest.json` existe no servidor
- [ ] Cache do Laravel foi limpo e recriado
- [ ] Permissões de `public/build/` estão corretas (755)
- [ ] Cache do navegador foi limpo

## Comandos Resumidos (Copiar e Colar)

```bash
cd /home1/gab51441/nerdola.akaigrupo.com.br/nerdola

# Remover arquivo hot se existir (indica modo dev)
rm -f public/hot

# Limpar todos os caches
php artisan optimize:clear
php artisan config:clear

# Verificar/Corrigir APP_ENV no .env
# Edite o .env e certifique-se de que tem: APP_ENV=production
nano .env

# Recriar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar
php artisan config:show app.env
ls -la public/build/manifest.json
chmod -R 755 public/build
```

## Script Automatizado

Use o script `fix-vite-production.sh` para automatizar todo o processo:

```bash
# 1. Fazer upload do script para o servidor via FTP
# 2. No servidor, executar:
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

## Se o Problema Persistir

1. **Verifique os logs do Laravel:**
```bash
tail -f storage/logs/laravel.log
```

2. **Verifique se o Document Root está correto:**
   - Deve apontar para `/caminho/para/projeto/public`

3. **Verifique permissões:**
```bash
ls -la public/
ls -la public/build/
```

4. **Teste temporariamente com debug ativado:**
   - No `.env`: `APP_DEBUG=true`
   - Recrie o cache: `php artisan config:clear && php artisan config:cache`
   - Isso mostrará erros mais detalhados (lembre-se de desativar depois!)

## Nota Importante

Nunca deixe `APP_DEBUG=true` em produção após resolver o problema. Sempre mantenha `APP_DEBUG=false` e `APP_ENV=production` em servidores de produção.

