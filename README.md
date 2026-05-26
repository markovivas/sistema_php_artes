# ArtES - Sistema de Gerenciamento de Artes

Sistema web completo para gestão de pedidos de arte com múltiplos perfis de usuário.

## Stack

- **PHP 8.2** (FPM)
- **MySQL 8.0**
- **Nginx**
- **Docker**

## Estrutura do Projeto

```
├── docker-compose.yml
├── nginx/
│   └── default.conf
├── php/
│   └── Dockerfile
└── site/
    ├── database.sql
    ├── login.php
    ├── index.php
    ├── includes/
    │   ├── config.php
    │   ├── db.php
    │   ├── auth.php
    │   ├── functions.php
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
    │   ├── users.php
    │   └── finances.php
    └── assets/
        ├── css/style.css
        ├── js/script.js
        └── uploads/orders/
```

## Como Rodar

```bash
docker-compose up -d
```

1. Acesse `http://localhost:8080` (phpMyAdmin)
2. Importe o arquivo `site/database.sql`
3. Acesse `http://localhost`

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
- Integração WhatsApp (WAHA API)
- Notificações em tempo real (Pusher/WebSocket)
- Automação com n8n
- Geração de PDF com DomPDF
- App mobile nativo
- CRM integrado
- Integração Canva/Figma
- Painel TV para produção
- Sistema multiempresa
