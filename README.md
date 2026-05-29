# ArtES - Sistema de Gerenciamento de Artes

Sistema web completo para gestão de pedidos de arte com múltiplos perfis de usuário.

## Identidade Visual

### Login (Glassmorphism Design)

| Cor | Hexadecimal | Uso |
|-----|-------------|-----|
| Azul brand | `#2563eb` | Botão primary, inputs foco |
| Azul accent | `#60a5fa` | Gradiente do botão |
| Azul soft | `#dbeafe` | Background alerta |
| Azul shadow | `rgba(37,99,235,.25)` | Sombra do botão |
| Slate 900 | `#0f172a` | Texto principal |
| Slate 500 | `#64748b` | Texto secundário |
| Slate 300 | `#94a3b8` | Placeholder dos inputs |
| Panel glass | `rgba(248,250,252,.92)` | Card login com blur |
| Line | `rgba(51,65,85,.12)` | Bordas do card e inputs |

### Sistema (demais páginas)

| Cor | Hexadecimal | Uso |
|-----|-------------|-----|
| Azul | `#40adec` | Primary, botões, links |
| Verde | `#88bd46` | Success, status positivos |
| Amarelo | `#f7c72b` | Warning, prioridade alta |
| Vermelho | `#e33e3c` | Danger, urgências |

### Tela de Login

A tela de login utiliza um design moderno com efeito **glassmorphism**:

- **Background:** Gradiente azul claro com `radial-gradient` e grid overlay
- **Card:** Fundo semi-transparente `rgba(248,250,252,.92)` com `backdrop-filter: blur(14px)`, borda sutil e cantos arredondados (`32px`)
- **Orbes flutuantes:** 3 esferas animadas com `blur(80px)` nas cores do brand
- **Formulário:** Inputs com padding generoso (`14px`), borda `14px` de raio e foco com glow azul
- **Botão:** Gradiente `#2563eb → #60a5fa` com sombra elevada e efeito hover
- **Responsivo:** Adapta card, inputs e orbes para mobile (breakpoint `540px`)

## Stack

- **PHP 8.2** (FPM, com extensão curl)
- **MySQL 8.0**
- **Nginx**
- **Docker**
- **WAHA API** (WhatsApp HTTP API)

## Estrutura do Projeto

```
├── docker-compose.yml
├── nginx/
│   └── default.conf
├── php/
│   └── Dockerfile
└── site/
    ├── database.sql
    ├── migration-whatsapp.sql
    ├── login.php
    ├── index.php
    ├── api/
    │   └── whatsapp-webhook.php
    ├── includes/
    │   ├── config.php
    │   ├── db.php
    │   ├── auth.php
    │   ├── functions.php
    │   ├── waha.php
    │   ├── header.php
    │   └── footer.php
    ├── client/
    │   ├── index.php
    │   ├── orders.php
    │   └── order-detail.php
    ├── designer/
    │   └── index.php
    ├── admin/
    │   ├── index.php
    │   ├── whatsapp.php
    │   ├── users.php
    │   └── finances.php
    └── assets/
        ├── css/style.css
        ├── js/script.js
        └── uploads/orders/
```

## Como Rodar

```bash
# 1. Iniciar containers
docker-compose up -d --build

# 2. Importar banco de dados
Acesse http://localhost:8080 (phpMyAdmin) e importe site/database.sql

# 3. Executar migration WhatsApp (se o banco já existir)
docker exec -i artes_db mysql -uroot -proot artes < site/migration-whatsapp.sql

# 4. Acessar http://localhost
```

## WhatsApp (WAHA API)

O sistema envia notificações automáticas via WhatsApp quando:

| Evento | Notificação |
|--------|-------------|
| Status muda para "Aguardando Cliente" | Cliente recebe aviso para aprovar |
| Status muda para "Finalizado" | Cliente recebe aviso com arquivos |
| Cliente aprova a arte | Designer é notificado |
| Cliente solicita ajustes | Designer é notificado |

**Para conectar:**

1. Acesse **Admin > WhatsApp**
2. Clique em "Conectar WhatsApp"
3. Escaneie o QR Code com o celular
4. Cadastre o número dos clientes em **Admin > Usuários** (campo WhatsApp, apenas números com DDD)

Mensagens recebidas dos clientes no WhatsApp são automaticamente adicionadas como comentários no pedido ativo.

### Credenciais WAHA

> As credenciais persistem no `docker-compose.yml` e são injetadas como variáveis de ambiente no container.

| Serviço | Usuário | Senha / Chave |
|---------|---------|---------------|
| Swagger UI (`http://localhost:3005`) | `admin` | `849c3b3edc224ff8ae3745e9b008852d` |
| Dashboard WAHA | `admin` | `849c3b3edc224ff8ae3745e9b008852d` |
| API Key (usada pelo PHP internamente) | — | `dec771db080c466da9a621b11e457358` |

**Atenção:** O Swagger UI e o Dashboard são acessíveis em `http://localhost:3005` — útil para depuração e envio manual de mensagens.

## Cadastro

Novos usuários podem se cadastrar como **Cliente** diretamente pela tela de login, clicando em "Cadastrar novo usuário". O formulário solicita nome, e-mail, senha, WhatsApp e setor.

## Usuários de Teste

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | admin@artes.com | 123456 |
| Designer | designer@artes.com | 123456 |
| Cliente | cliente@artes.com | 123456 |
| Financeiro | financeiro@artes.com | 123456 |
| Produção | producao@artes.com | 123456 |

## Funcionalidades

### Painel do Cliente
- Dashboard com cards de pedidos em andamento, aprovação pendente, finalizados
- Abertura de pedidos com briefing completo
- Timeline do pedido
- Aprovação online de artes
- Downloads de arquivos (PNG, PDF, CDR, AI, MP4)
- Chat interno por pedido

### Painel do Designer
- Kanban estilo Trello com colunas: Novos, Em Produção, Ajustes, Aguardando Cliente, Finalizados
- Upload de arquivos com versionamento
- Atribuição de designers
- Prioridade com cores: urgente, alta, normal, baixa

### Painel Administrativo
- KPIs: pedidos do dia, produção ativa, faturamento mensal, ticket médio
- Financeiro: contas a pagar/receber, fluxo de caixa
- Gerenciamento de usuários (CRUD completo)
- Conexão WhatsApp via QR Code (WAHA API)

## Níveis de Usuário

| Perfil | Permissões |
|--------|------------|
| Cliente | Acompanhar pedidos e aprovar artes |
| Designer | Produzir artes e gerenciar tarefas |
| Produção | Visualizar produção |
| Financeiro | Gerenciar pagamentos |
| Admin | Acesso completo ao sistema |

## Funcionalidades Futuras

- Menu contextual nos cards do Kanban (detalhes e troca de status com dropdown)
- Notificações em tempo real (Pusher/WebSocket)
- Automação com n8n
- Geração de PDF com DomPDF
- App mobile nativo
- CRM integrado
- Integração Canva/Figma
- Painel TV para produção
- Sistema multiempresa
