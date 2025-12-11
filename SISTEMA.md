# Sistema Nerdola Bank - Documentação

## Visão Geral

O **Nerdola Bank** é uma plataforma completa de transações financeiras usando a moeda digital fictícia **Nerdola (NDL)**. O sistema foi desenvolvido com Laravel 10 e Blade, com foco em interface mobile-first usando Tailwind CSS.

## Arquitetura

### Backend
- **Framework**: Laravel 10
- **ORM**: Eloquent
- **Autenticação**: Laravel Auth
- **Banco de Dados**: MySQL (configurável)

### Frontend
- **Template Engine**: Blade
- **CSS Framework**: Tailwind CSS
- **Build Tool**: Vite
- **Design**: Mobile-first responsivo

## Estrutura de Dados

### Tabelas Principais

#### users
- Armazena usuários (clientes e administradores)
- Campos: id, name, email, password, phone, role, is_active

#### wallets
- Carteiras digitais dos usuários
- Campos: id, user_id, code (ex: NDL123456), balance

#### transactions
- Registro de todas as transações
- Campos: type (deposit/payment/transfer), amount, from/to wallets/users, status

#### qr_codes
- QR Codes gerados para pagamentos e depósitos
- Campos: token, type, amount, expires_at, usage_limit, times_used

## Funcionalidades Implementadas

### Para Clientes

1. **Dashboard**
   - Visualização do saldo atual
   - Últimas 5 transações
   - Botões de acesso rápido

2. **Extrato**
   - Listagem completa de transações
   - Filtros por tipo, data
   - Paginação

3. **Transferências**
   - Transferir NDL para outras carteiras
   - Busca por código NDL, email ou telefone
   - Validação de saldo

4. **QR Code de Recebimento**
   - Gerar QR Code para receber pagamentos
   - Valor fixo ou aberto
   - Configuração de expiração e limite de uso

5. **Pagamento via QR Code**
   - Ler QR Code de outro usuário
   - Confirmar pagamento
   - Validação automática

### Para Administradores

1. **Dashboard**
   - Estatísticas gerais
   - Total de usuários (ativos/bloqueados)
   - Volume transacionado
   - Transações recentes

2. **Gestão de Usuários**
   - Listar todos os clientes
   - Visualizar detalhes de cada usuário
   - Bloquear/Desbloquear contas
   - Busca e filtros

3. **Depósitos**
   - Criar depósito manual para cliente
   - Gerar QR Code de depósito
   - Histórico de depósitos

4. **Gestão de Transações**
   - Consultar todas as transações
   - Filtros avançados (tipo, status, usuário, data)
   - Detalhes completos

## Regras de Negócio Implementadas

### Transações

1. **Saldo**: Não permite saldo negativo
2. **Validação**: Verifica saldo antes de cada transação
3. **Status**: Todas as transações têm status (pending/completed/cancelled/failed)
4. **Auditoria**: Registro completo de origem e destino

### Usuários

1. **Clientes**: Podem pagar, receber e transferir
2. **Administradores**: Podem criar depósitos, mas não recebem transferências
3. **Bloqueio**: Usuários bloqueados não podem transacionar

### QR Codes

1. **Expiração**: Pode ter data de expiração
2. **Limite de uso**: Pode ser configurado (padrão: 1 uso)
3. **Validação**: Verifica validade antes de processar
4. **Valor**: Pode ser fixo ou aberto para o usuário informar

## Segurança

- ✅ Autenticação com email e senha
- ✅ Hash de senhas com bcrypt
- ✅ Middleware de verificação de conta ativa
- ✅ Validação de saldo
- ✅ Proteção CSRF (nativo Laravel)
- ✅ Validação de entrada em todos os formulários
- ✅ Transações de banco de dados para consistência

## Interface

### Mobile-First
- Design responsivo otimizado para celular
- Menu inferior fixo (tipo app bancário)
- Botões grandes e legíveis
- Navegação intuitiva

### Web (Desktop)
- Layout adaptável
- Tabelas responsivas
- Menu superior para navegação

## Fluxos Principais

### 1. Cadastro e Login
1. Usuário se cadastra informando nome, email, telefone e senha
2. Sistema cria carteira automaticamente com código NDL
3. Login com email e senha

### 2. Transferência
1. Cliente acessa "Transferir"
2. Informa destino (código/email/telefone) e valor
3. Sistema valida destino, saldo e permissões
4. Executa transferência e atualiza saldos

### 3. Pagamento via QR Code
1. Recebedor gera QR Code de recebimento
2. Pagador lê QR Code (via app/câmera)
3. Sistema mostra detalhes e confirmação
4. Pagador confirma pagamento
5. Sistema valida e processa pagamento

### 4. Depósito Admin
1. Admin cria depósito ou gera QR Code
2. Para depósito manual: seleciona cliente e valor
3. Para QR Code: gera e distribui
4. Quando processado, credita na carteira do cliente

## Próximas Melhorias Sugeridas

- [ ] Sistema de notificações (email/SMS/push)
- [ ] Histórico de QR Codes gerados
- [ ] Exportação de extratos (PDF/CSV)
- [ ] Sistema de contatos frequentes
- [ ] Relatórios avançados para admin
- [ ] API REST para integrações
- [ ] Testes automatizados (PHPUnit)
- [ ] Rate limiting
- [ ] Auditoria de ações admin
- [ ] Perfil do usuário (editar dados)
- [ ] Recuperação de senha

## Tecnologias e Bibliotecas

- Laravel 10
- Tailwind CSS
- SimpleSoftwareIO QrCode
- Vite
- Axios

## Licença

MIT

