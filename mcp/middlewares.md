# Middlewares e Fluxo de Autenticação

## Middleware Groups

### `web` (rotas em `routes/web.php` e `routes/settings.php`)

Configuração em `bootstrap/app.php`:

```
Sessão → CSRF → Cookies → HandleAppearance → HandleInertiaRequests → AddLinkHeadersForPreloadedAssets
```

- **`HandleAppearance`** — Lê o cookie `appearance` (light/dark/system) e define o header para o tema.
- **`HandleInertiaRequests`** — Compartilha via Inertia props: `name` (app name), `auth.user`, `sidebarOpen`.
- `encryptCookies` ignora: `appearance`, `sidebar_state`.

### `api` (rotas em `routes/api.php`)

Carregado pelo Laravel automaticamente com prefix `/api/`:
```
ThrottleRequests(60/min) → SubstituteBindings
```

> **Sem CSRF** — rotas API são stateless por design.

---

## Guards de Autenticação

| Guard | Driver | Uso |
|-------|--------|-----|
| `web` | session | Rotas web (Fortify, settings, dashboard) |
| `sanctum` | token | Rotas API (`auth:sanctum`) |

---

## Fluxo Completo de Autenticação

```
[Browser]
   ├─ POST /login → Fortify → cria sessão → redireciona /dashboard
   │
   ├─ GET /dashboard [auth, verified]
   │   onMounted() → POST /auth/token/session [auth web]
   │               → TokenController@issueForSession
   │               → salva token em localStorage.api_token
   │
   ├─ Todas chamadas axios: Authorization: Bearer <localStorage.api_token>
   │
   ├─ POST /api/notifications/subscribe  [auth:sanctum]
   ├─ GET  /api/notifications/subscriptions  [auth:sanctum]
   └─ POST /api/notifications/test  [auth:sanctum]

[App Externo / Mobile]
   └─ POST /api/auth/token (email+password) → retorna Bearer token
```

---

## Middleware de Rotas

| Middleware | Descrição |
|------------|----------|
| `auth` | Requer sessão web ativa (guard `web`) |
| `verified` | Requer email verificado |
| `auth:sanctum` | Requer Bearer token válido |
| `throttle:login` | 5 tentativas/min por email+IP |
| `throttle:6,1` | 6 req/min (troca de senha) |

---

## Comportamento em Testes

`tests/TestCase.php` desabilita `ValidateCsrfToken` globalmente via `withoutMiddleware()`.
Testes usam sessão `array` e SQLite in-memory — sem cookies reais.
