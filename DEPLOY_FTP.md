# Guia de Deploy via FTP - Nerdola Bank

## Pré-requisitos

- Cliente FTP (FileZilla, WinSCP, Cyberduck, etc.)
- Acesso SSH ao servidor (para executar comandos)
- PHP >= 8.1 no servidor
- Composer instalado no servidor
- Node.js e NPM (apenas para compilar assets localmente)
- MySQL/MariaDB configurado

## Passo 1: Preparar o Projeto Localmente

### 1.1. Compilar Assets para Produção

```bash
npm run build
```

Isso gerará os arquivos em `public/build/` que devem ser enviados ao servidor.

### 1.2. Verificar Arquivos a NÃO Enviar

**NÃO faça upload dos seguintes diretórios/arquivos:**

- `node_modules/` - Dependências Node.js (não necessárias em produção)
- `.git/` - Controle de versão
- `.env` - Arquivo de configuração local (criar novo no servidor)
- `storage/logs/*.log` - Logs locais
- `storage/framework/cache/*` - Cache local
- `storage/framework/sessions/*` - Sessões locais
- `storage/framework/views/*` - Views compiladas locais
- `bootstrap/cache/*.php` - **CRÍTICO: Cache de bootstrap (contém caminhos do Windows, será gerado no servidor)**
- `.vscode/`, `.idea/`, `.fleet/` - Configurações de IDE
- `tests/` - Testes (opcional, não necessário em produção)

**⚠️ IMPORTANTE:** Se você já enviou `bootstrap/cache/*.php` para o servidor, DELETE esses arquivos no servidor antes de continuar. Eles contêm caminhos absolutos do seu sistema Windows e causarão erros no servidor Linux.

## Passo 2: Upload via FTP

### 2.1. Estrutura de Diretórios no Servidor

O Laravel geralmente é configurado de duas formas:

**Opção A: Document Root aponta para `/public` (Recomendado)**
```
/public_html/ (ou /www/ ou /htdocs/)
├── index.php
├── .htaccess
└── build/
```

**Opção B: Projeto completo na raiz**
```
/public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
└── ...
```

### 2.2. Arquivos e Diretórios para Upload

Faça upload de TODOS os seguintes diretórios e arquivos:

```
✅ app/
✅ bootstrap/ (mas NÃO bootstrap/cache/*.php)
✅ config/
✅ database/
✅ public/ (incluindo public/build/)
✅ resources/
✅ routes/
✅ storage/ (estrutura de diretórios, mas não arquivos de cache)
✅ vendor/ (ou instalar via Composer no servidor - RECOMENDADO)
✅ artisan
✅ composer.json
✅ composer.lock
✅ package.json
✅ package-lock.json
✅ vite.config.js
✅ tailwind.config.js
✅ postcss.config.js
```

### 2.3. Criar Estrutura de Diretórios no Servidor

Certifique-se de que os seguintes diretórios existem e têm permissões corretas:

```bash
storage/
storage/app/
storage/framework/
storage/framework/cache/
storage/framework/cache/data/
storage/framework/sessions/
storage/framework/views/
storage/logs/
bootstrap/cache/
```

## Passo 3: Configuração no Servidor

### 3.1. Conectar via SSH

Conecte-se ao servidor via SSH para executar os comandos.

### 3.2. Criar Arquivo .env

Crie o arquivo `.env` no servidor com as configurações de produção:

```bash
cd /caminho/para/seu/projeto
nano .env
```

Configure o `.env`:

```env
APP_NAME="Nerdola Bank"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://nerdola.akaigrupo.com.br

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nome_do_banco
DB_USERNAME=usuario_banco
DB_PASSWORD=senha_banco

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=seu_smtp
MAIL_PORT=587
MAIL_USERNAME=seu_email
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@nerdola.akaigrupo.com.br"
MAIL_FROM_NAME="${APP_NAME}"
```

### 3.3. Gerar Chave da Aplicação

```bash
php artisan key:generate
```

### 3.4. Instalar Dependências PHP (se não enviou vendor/)

```bash
composer install --no-dev --optimize-autoloader
```

**Importante:** Use `--no-dev` para não instalar dependências de desenvolvimento.

### 3.5. Configurar Permissões

```bash
# Dar permissão de escrita para storage e bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Nota:** `www-data` pode variar (pode ser `apache`, `nginx`, `httpd`, etc.). Verifique com seu provedor.

### 3.6. Executar Migrations

```bash
php artisan migrate --force
```

O flag `--force` é necessário em produção.

### 3.7. Popular Dados Iniciais (Opcional)

```bash
php artisan db:seed
```

Isso criará o usuário do sistema e usuários de teste.

### 3.8. Limpar e Otimizar Cache

```bash
# Limpar todos os caches
php artisan optimize:clear

# Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3.9. Criar Link Simbólico para Storage (se necessário)

```bash
php artisan storage:link
```

## Passo 4: Configuração do Servidor Web

### 4.1. Apache (.htaccess)

Se usar Apache, o arquivo `public/.htaccess` já deve estar configurado. Certifique-se de que o `mod_rewrite` está habilitado.

### 4.2. Nginx

Se usar Nginx, configure o servidor virtual:

```nginx
server {
    listen 80;
    server_name nerdola.akaigrupo.com.br;
    root /caminho/para/projeto/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4.3. Document Root

Certifique-se de que o Document Root do servidor aponta para o diretório `public/` do projeto:

```
Document Root: /caminho/para/projeto/public
```

## Passo 5: Verificações Pós-Deploy

### 5.1. Verificar Permissões

```bash
ls -la storage/
ls -la bootstrap/cache/
```

Ambos devem ter permissões de escrita (775 ou 755).

### 5.2. Verificar Arquivo .env

```bash
cat .env | grep APP_KEY
```

Deve mostrar uma chave gerada (não vazia).

### 5.3. Testar Aplicação

Acesse a URL do site e verifique:
- Página de login carrega
- Não há erros de permissão
- Assets (CSS/JS) carregam corretamente

### 5.4. Verificar Logs

```bash
tail -f storage/logs/laravel.log
```

## Passo 6: Script de Deploy Automatizado (Opcional)

Crie um script `deploy-ftp.sh` para automatizar comandos no servidor:

```bash
#!/bin/bash

echo "🚀 Iniciando deploy..."

# Navegar para o diretório do projeto
cd /caminho/para/projeto

# Limpar cache
echo "🧹 Limpando cache..."
php artisan optimize:clear

# Atualizar dependências (se necessário)
echo "📦 Atualizando dependências..."
composer install --no-dev --optimize-autoloader --no-interaction

# Executar migrations (se houver novas)
echo "🗄️ Executando migrations..."
php artisan migrate --force

# Otimizar para produção
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Limpar cache de aplicação
php artisan cache:clear

echo "✅ Deploy concluído!"
```

Torne o script executável:

```bash
chmod +x deploy-ftp.sh
```

Execute após cada upload:

```bash
./deploy-ftp.sh
```

## Checklist de Deploy

- [ ] Assets compilados localmente (`npm run build`)
- [ ] Upload de todos os arquivos necessários via FTP
- [ ] Arquivo `.env` criado e configurado no servidor
- [ ] `APP_KEY` gerada no servidor
- [ ] Dependências PHP instaladas (`composer install --no-dev`)
- [ ] Permissões configuradas (storage, bootstrap/cache)
- [ ] Migrations executadas
- [ ] Seeder executado (se necessário)
- [ ] Cache limpo e otimizado
- [ ] Document Root aponta para `/public`
- [ ] Testes realizados na aplicação
- [ ] Logs verificados

## Problemas Comuns

### Erro: "Class CollisionServiceProvider not found"

**Solução:**
```bash
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
php artisan config:cache
```

### Erro: "Permission denied" em storage

**Solução:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Erro: "No application encryption key"

**Solução:**
```bash
php artisan key:generate
```

### Assets não carregam (CSS/JS) - Erro Vite Dev Server

**Sintomas:**
- Página em branco
- Console do navegador mostra: `GET http://[::1]:5173/@vite/client net::ERR_CONNECTION_REFUSED`
- Erros tentando carregar `app.css` e `app.js` do servidor Vite

**Causa:**
O Laravel está tentando usar o Vite em modo de desenvolvimento em vez dos assets compilados.

**Solução:**

1. **Verifique o `.env` no servidor:**
```bash
cat .env | grep APP_ENV
```
Deve mostrar: `APP_ENV=production`

2. **Verifique se `public/build/manifest.json` existe:**
```bash
ls -la public/build/manifest.json
```
Se não existir, você precisa compilar os assets localmente e fazer upload da pasta `public/build/`:
```bash
# No seu computador local:
npm run build
# Depois faça upload da pasta public/build/ via FTP
```

3. **Limpe todos os caches e recrie:**
```bash
cd /caminho/para/projeto

# Limpar todos os caches
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Verificar/Corrigir APP_ENV no .env
# Edite o .env e certifique-se de que tem:
# APP_ENV=production
# APP_DEBUG=false

# Recriar cache de configuração
php artisan config:cache

# Verificar se o cache foi criado corretamente
php artisan config:show app.env
# Deve mostrar: "production"
```

4. **Verifique permissões:**
```bash
chmod -R 755 public/build
```

5. **Limpe o cache do navegador** (Ctrl+Shift+R ou Ctrl+F5)

**Solução Rápida (Script):**
```bash
cd /caminho/para/projeto
php artisan optimize:clear
php artisan config:clear
# Edite .env e defina APP_ENV=production
php artisan config:cache
```

### Erro 500 Internal Server Error

**Solução:**
1. Verifique logs: `storage/logs/laravel.log`
2. Verifique permissões de arquivos
3. Verifique se `.env` está configurado
4. Verifique se `APP_DEBUG=true` temporariamente para ver erros

### Erro: Caminhos do Windows no Servidor Linux

**Sintomas:**
- Erro: `file_put_contents(C:\Meus Arquivos\...): Failed to open stream`
- O Laravel está tentando usar caminhos do Windows no servidor Linux

**Causa:**
Os arquivos de cache em `bootstrap/cache/*.php` foram gerados no Windows e contêm caminhos absolutos do Windows. Esses arquivos foram enviados para o servidor.

**Solução:**

1. **DELETE os arquivos de cache no servidor:**
```bash
cd /caminho/para/projeto
rm -f bootstrap/cache/*.php
```

2. **Limpe todos os caches:**
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

3. **Verifique se os diretórios de storage existem:**
```bash
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache
mkdir -p storage/framework/views
mkdir -p storage/logs
```

4. **Configure permissões:**
```bash
chmod -R 775 storage bootstrap/cache
```

5. **Recrie o cache no servidor (não no Windows!):**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. **Verifique se funcionou:**
```bash
php artisan config:show app.env
# Deve mostrar: "production"
```

**Prevenção:**
- NUNCA envie `bootstrap/cache/*.php` via FTP
- Sempre delete esses arquivos no servidor antes de fazer deploy
- Sempre gere o cache no servidor, nunca no Windows

## Comandos Rápidos de Deploy

Após fazer upload via FTP, execute no servidor:

```bash
cd /caminho/para/projeto
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Notas Importantes

1. **Nunca faça upload do `.env` local** - Crie um novo no servidor
2. **Sempre use `--no-dev`** ao instalar dependências em produção
3. **Mantenha `APP_DEBUG=false`** em produção
4. **Faça backup** antes de executar migrations em produção
5. **Verifique permissões** após cada deploy
6. **Monitore logs** após o deploy para identificar problemas

## Suporte

Em caso de problemas:
1. Verifique `storage/logs/laravel.log`
2. Verifique permissões de arquivos e diretórios
3. Verifique configurações do `.env`
4. Verifique se todas as dependências foram instaladas

