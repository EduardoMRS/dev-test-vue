# Task Manager — Laravel + Vue + Inertia

Aplicação de gerenciamento de tarefas construída com **Laravel 12**, **Vue 3**, **Inertia.js** e **PostgreSQL**, rodando 100 % em Docker utilizando template base disponivel em https://github.com/laravel/vue-starter-kit

## Funcionalidades

- CRUD completo de tarefas com paginação, busca e filtros (pendentes / concluídas / atrasadas)
- Notificações por e-mail e push (Web Push) com agendamento automático
- Configuração global de notificações (dias antes do vencimento, e-mail e push on/off)
- Toggles de notificação por tarefa (e-mail / push individualmente)
- Autenticação com Fortify (login, registro, verificação de e-mail, 2FA)
- Internacionalização com vue-i18n (pt-BR padrão, inglês disponível)
- Landing page pública com credenciais de demonstração
- Dark mode (aparência configurável nas settings)
- PWA com suporte a instalação

## Requisitos

- Docker e Docker Compose

## Início rápido

```bash
# 1. Clone o repositório
git clone <lembrar_de_colocar_a_url> && cd laravel_vue_template

# 2. Copie e ajuste o .env
cp .env.example .env

# 3. Suba tudo com Docker
docker-compose up -d
```

O entrypoint do container executa automaticamente:

- `composer install`
- `php artisan key:generate` (se necessário)
- `php artisan migrate --force`
- `php artisan db:seed --force`
- Build dos assets (`npm run build`)

Após a inicialização, acesse **http://localhost** (ou a porta configurada).

## Credenciais de demonstração

| Usuário       | E-mail             | Senha      |
| ------------- | ------------------ | ---------- |
| João Silva    | joao@teste.com     | 12345678   |
| Maria Santos  | maria@teste.com    | 12345678   |

## Serviços Docker

| Serviço     | Descrição                              | Porta padrão |
| ----------- | -------------------------------------- | ------------ |
| app         | FrankenPHP / Caddy + PHP 8.3           | 80 / 443     |
| db          | PostgreSQL 16                          | 5432         |
| redis       | Redis 7                                | 6379         |
| queue       | Worker de filas (Laravel Queue)        | —            |
| scheduler   | Schedule runner (`schedule:run` 60 s)  | —            |
| mail        | Mailpit (captura de e-mails)           | 8025         |

## Stack técnica

- **Backend:** Laravel 12, Fortify, Sanctum, Inertia.js v2
- **Frontend:** Vue 3.5, TypeScript, Tailwind CSS v4, reka-ui / shadcn-vue
- **Banco:** PostgreSQL 16
- **i18n:** vue-i18n (pt-BR / en)
- **Notificações:** laravel-notification-channels/webpush + Mail
- **Routes:** Wayfinder (rotas tipadas no TypeScript)

## Estrutura relevante

```
app/
  Console/Commands/SendTaskReminders.php   # Comando de envio de lembretes
  Http/Controllers/TaskController.php      # CRUD de tarefas
  Http/Controllers/Settings/              # Configuração de notificações
  Models/Task.php                          # Model de tarefa
  Models/NotificationSetting.php           # Configuração de notificação
  Notifications/TaskReminderNotification.php
resources/js/
  pages/tasks/                             # Páginas Vue de tarefas
  pages/settings/Notifications.vue         # Configuração de notificações
  locales/                                 # Traduções pt-BR e en
  plugins/i18n.ts                          # Setup do vue-i18n
database/
  migrations/                              # Migrations (tasks, notification_settings, task_notifications)
  seeders/DatabaseSeeder.php               # Seed com 2 usuários + 20 tarefas
mcp/                                       # Documentação MCP para IAs
```

## Comandos úteis

```bash
# Logs do container
./art logs

# Executar comandos artisan
./art

# Executar comandos php
./art php

# Executar comandos composer
./art composer

# Executar comandos node
./art npm
```