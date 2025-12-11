# Nerdola Bank

Sistema de transações financeiras usando a moeda digital fictícia Nerdola (NDL).

## Características

- 🏦 Carteira digital para cada usuário
- 💳 Pagamentos via QR Code
- 📱 Interface mobile-first responsiva
- 👤 Dois tipos de usuário: Cliente e Administrador
- 🔐 Sistema de autenticação seguro
- 📊 Painel administrativo completo

## Requisitos

- PHP >= 8.1
- Composer
- Node.js e NPM
- MySQL ou outro banco de dados suportado

## Instalação

1. Clone o repositório
2. Instale as dependências do PHP:
```bash
composer install
```

3. Instale as dependências do Node:
```bash
npm install
```

4. Copie o arquivo .env.example para .env:
```bash
cp .env.example .env
```

5. Gere a chave da aplicação:
```bash
php artisan key:generate
```

6. Configure o banco de dados no arquivo .env

7. Execute as migrações:
```bash
php artisan migrate
```

8. Execute as seeders para criar dados iniciais:
```bash
php artisan db:seed
```

9. Compile os assets:
```bash
npm run dev
```

## Executar o servidor

```bash
php artisan serve
```

Acesse: http://localhost:8000

## Credenciais padrão

Após executar as seeders, você terá acesso a:

**Administrador:**
- Email: admin@nerdola.com
- Senha: password

**Cliente de teste:**
- Email: cliente@nerdola.com
- Senha: password

## Estrutura do Projeto

- `app/Models` - Modelos Eloquent
- `app/Http/Controllers` - Controladores
- `app/Http/Middleware` - Middlewares
- `database/migrations` - Migrações do banco
- `resources/views` - Views Blade
- `routes` - Rotas da aplicação

