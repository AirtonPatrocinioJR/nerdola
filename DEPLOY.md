# Instruções de Deploy

## Problema Comum: CollisionServiceProvider não encontrado

Este erro ocorre quando o cache do Laravel foi gerado em ambiente de desenvolvimento e inclui providers que não estão disponíveis em produção.

## Solução Rápida

Execute os seguintes comandos no servidor após fazer o deploy:

```bash
# Limpar todos os caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerar cache apenas com dependências de produção
composer install --no-dev --optimize-autoloader

# Regenerar cache otimizado para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Script Automatizado

Você pode usar o script `deploy.sh` fornecido:

```bash
chmod +x deploy.sh
./deploy.sh
```

## Checklist de Deploy

- [ ] Fazer upload dos arquivos para o servidor
- [ ] Executar `composer install --no-dev` no servidor
- [ ] Limpar todos os caches do Laravel
- [ ] Regenerar cache otimizado
- [ ] Verificar permissões de diretórios (storage, bootstrap/cache)
- [ ] Verificar arquivo .env está configurado corretamente

## Nota Importante

Os arquivos de cache (`bootstrap/cache/*.php`) agora estão no `.gitignore` e não devem ser commitados. Eles serão gerados automaticamente no servidor após o deploy.

