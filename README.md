# ArtES - Sistema de Gerenciamento de Artes

Sistema web completo para gestão de pedidos de arte com múltiplos perfis de usuário.

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
