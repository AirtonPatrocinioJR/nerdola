# Guia de Instalação - Nerdola Bank

## Pré-requisitos

- PHP >= 8.1
- Composer
- Node.js >= 16 e NPM
- MySQL ou outro banco de dados compatível
- Servidor web (Apache/Nginx) ou PHP Built-in Server

## Passos de Instalação

### 1. Instalar Dependências PHP

```bash
composer install
```

### 2. Instalar Dependências Node

```bash
npm install
```

### 3. Configurar Ambiente

Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Edite o arquivo `.env` e configure:

- `APP_NAME`: Nome da aplicação
- `APP_URL`: URL da aplicação
- `DB_CONNECTION`: Tipo de banco (mysql, pgsql, etc)
- `DB_HOST`: Host do banco de dados
- `DB_PORT`: Porta do banco de dados
- `DB_DATABASE`: Nome do banco de dados
- `DB_USERNAME`: Usuário do banco
- `DB_PASSWORD`: Senha do banco

### 4. Gerar Chave da Aplicação

```bash
php artisan key:generate
```

### 5. Criar Banco de Dados

Crie o banco de dados manualmente ou execute:

```bash
php artisan db:create
```

### 6. Executar Migrations

```bash
php artisan migrate
```

### 7. Popular Dados Iniciais (Opcional)

Para criar usuários de teste:

```bash
php artisan db:seed
```

Isso criará:
- **Administrador**: admin@nerdola.com / password
- **Cliente**: cliente@nerdola.com / password (com 1000 NDL de saldo)

### 8. Compilar Assets

Para desenvolvimento:

```bash
npm run dev
```

Para produção:

```bash
npm run build
```

### 9. Iniciar Servidor

Para desenvolvimento:

```bash
php artisan serve
```

Acesse: http://localhost:8000

## Estrutura do Projeto

```
nerdola/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/      # Controllers do admin
│   │   │   ├── Client/     # Controllers do cliente
│   │   │   └── Auth/       # Controllers de autenticação
│   │   └── Middleware/     # Middlewares de autenticação/autorização
│   ├── Models/             # Models Eloquent
│   └── Services/           # Serviços de negócio
├── database/
│   ├── migrations/         # Migrations do banco
│   └── seeders/           # Seeders para dados iniciais
├── resources/
│   ├── views/             # Views Blade
│   │   ├── admin/         # Views do admin
│   │   ├── client/        # Views do cliente
│   │   ├── auth/          # Views de autenticação
│   │   └── layouts/       # Layouts base
│   ├── css/               # CSS/Tailwind
│   └── js/                # JavaScript
├── routes/
│   └── web.php            # Rotas da aplicação
└── public/                # Arquivos públicos
```

## Funcionalidades Implementadas

### Para Clientes

✅ Dashboard com saldo e últimas transações
✅ Visualizar extrato completo com filtros
✅ Efetuar transferências entre carteiras
✅ Gerar QR Code de recebimento (pagamento)
✅ Pagar via QR Code
✅ Interface mobile-first responsiva

### Para Administradores

✅ Dashboard com estatísticas
✅ Gestão de usuários (listar, visualizar, bloquear/desbloquear)
✅ Criar depósitos manualmente para clientes
✅ Gerar QR Codes de depósito
✅ Consultar e filtrar transações
✅ Interface web completa

### Segurança

✅ Autenticação com e-mail e senha
✅ Hash de senhas com bcrypt
✅ Middleware de verificação de conta ativa
✅ Validação de saldo antes de transações
✅ Proteção contra transferências para admins
✅ Validação de QR Codes (expiração e limite de uso)

## Próximos Passos Sugeridos

- [ ] Implementar notificações (email/push)
- [ ] Adicionar testes automatizados
- [ ] Implementar rate limiting
- [ ] Adicionar relatórios avançados para admin
- [ ] Implementar sistema de contatos frequentes
- [ ] Adicionar histórico de QR Codes gerados
- [ ] Implementar exportação de extratos (PDF/CSV)
- [ ] Adicionar API REST para integrações

## Suporte

Em caso de problemas, verifique:

1. Se todas as dependências foram instaladas
2. Se o banco de dados está configurado corretamente
3. Se as migrations foram executadas
4. Se o servidor está rodando corretamente
5. Logs em `storage/logs/laravel.log`

