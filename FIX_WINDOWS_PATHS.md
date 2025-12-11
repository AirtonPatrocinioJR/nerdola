# Correção: Caminhos do Windows no Servidor Linux

## Problema

Ao acessar `http://nerdola.akaigrupo.com.br/`, aparece o erro:

```
file_put_contents(C:\Meus Arquivos\Projetos\nerdola\storage\framework/sessions/...): 
Failed to open stream: No such file or directory
```

## Causa

Os arquivos de cache em `bootstrap/cache/*.php` foram gerados no Windows e contêm caminhos absolutos do Windows (como `C:\Meus Arquivos\...`). Quando esses arquivos foram enviados para o servidor Linux, o Laravel tentou usar esses caminhos incorretos.

## Solução Imediata

Execute os seguintes comandos **no servidor via SSH**:

```bash
# 1. Navegar para o diretório do projeto
cd /home1/gab51441/nerdola.akaigrupo.com.br/nerdola

# 2. DELETAR os arquivos de cache (CRÍTICO!)
rm -f bootstrap/cache/*.php

# 3. Limpar todos os caches do Laravel
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Verificar/Criar diretórios de storage
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache
mkdir -p storage/framework/views
mkdir -p storage/logs

# 5. Configurar permissões
chmod -R 775 storage bootstrap/cache

# 6. Recriar cache no servidor (não no Windows!)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Verificar se funcionou
php artisan config:show app.env
# Deve mostrar: "production"
```

## Verificação

Após executar os comandos:

1. **Teste a aplicação:** Acesse `http://nerdola.akaigrupo.com.br/`
2. **Verifique os logs:** `tail -f storage/logs/laravel.log`
3. **Verifique permissões:**
```bash
ls -la storage/framework/sessions/
ls -la bootstrap/cache/
```

## Prevenção Futura

### ⚠️ NUNCA faça upload de:

- `bootstrap/cache/*.php` - Esses arquivos contêm caminhos absolutos do sistema onde foram gerados
- `storage/framework/cache/*` - Cache local
- `storage/framework/sessions/*` - Sessões locais
- `storage/framework/views/*` - Views compiladas locais

### ✅ Sempre faça no servidor:

1. Delete os arquivos de cache antes de fazer deploy:
```bash
rm -f bootstrap/cache/*.php
```

2. Gere o cache no servidor após o deploy:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Checklist de Deploy

- [ ] Delete `bootstrap/cache/*.php` no servidor
- [ ] Faça upload dos arquivos via FTP (SEM os arquivos de cache)
- [ ] Execute `php artisan optimize:clear` no servidor
- [ ] Verifique se os diretórios de storage existem
- [ ] Configure permissões (`chmod -R 775 storage bootstrap/cache`)
- [ ] Gere o cache no servidor (`php artisan config:cache`)
- [ ] Teste a aplicação

## Comandos Resumidos (Copiar e Colar)

```bash
cd /home1/gab51441/nerdola.akaigrupo.com.br/nerdola
rm -f bootstrap/cache/*.php
php artisan optimize:clear
mkdir -p storage/framework/{sessions,cache,views} storage/logs
chmod -R 775 storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Nota Importante

Os arquivos de cache (`bootstrap/cache/*.php`) são específicos do sistema operacional onde foram gerados. Eles contêm caminhos absolutos e configurações específicas do ambiente. Por isso, **sempre devem ser gerados no servidor de produção**, nunca no ambiente de desenvolvimento.

