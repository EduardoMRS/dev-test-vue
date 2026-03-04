# Estrutura de Pastas

```
laravel_vue_template/
├── app/
│   ├── Actions/Fortify/          # CreateNewUser, ResetUserPassword
│   ├── Concerns/                 # PasswordValidationRules, ProfileValidationRules (traits)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/TokenController.php      # Emissão/revogação de Bearer tokens
│   │   │   ├── NotificationController.php    # Push notifications (subscribe/send)
│   │   │   └── Settings/                     # Profile, Password, TwoFactor
│   │   ├── Middleware/
│   │   │   ├── HandleAppearance.php          # Lê cookie 'appearance' e define tema
│   │   │   └── HandleInertiaRequests.php     # Compartilha auth.user e name com Inertia
│   │   └── Requests/Settings/            # Form requests para profile e password
│   ├── Models/
│   │   ├── User.php                      # HasApiTokens, HasPushSubscriptions, TwoFactor
│   │   └── PushSubscription.php          # Estende o model do pacote webpush
│   ├── Notifications/
│   │   └── WebPushNotification.php       # Notificação push com factory createGeneral()
│   └── Providers/
│       ├── AppServiceProvider.php        # configureDefaults (CarbonImmutable, passwords)
│       └── FortifyServiceProvider.php    # Views, actions e rate limiting do Fortify
├── bootstrap/
│   ├── app.php                       # Bootstrapping: routing (web + api), middlewares
│   └── providers.php
├── config/
│   ├── fortify.php                   # Guard web, features (registration, 2FA, etc.)
│   ├── auth.php                      # Guard web (session), provider Eloquent
│   └── webpush.php                   # VAPID keys (env: VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY)
├── database/
│   ├── factories/UserFactory.php
│   ├── migrations/                   # users, cache, jobs, 2FA, push_subscriptions, PAT
│   └── seeders/DatabaseSeeder.php
├── mcp/
│   ├── server/                       # Servidor MCP (Node.js/TypeScript)
│   └── *.md                          # Documentação estruturada
├── public/
│   ├── sw.js                         # Service Worker para Web Push / PWA
│   └── build/                        # Assets compilados pelo Vite
├── resources/
│   └── js/
│       ├── app.ts                    # Entry point: Inertia, Toaster, Notify, PWA
│       ├── pages/                    # Páginas Inertia (Dashboard, Welcome, auth/*, settings/*)
│       ├── components/               # PushToggle, InstallGuideIOS, AppSidebar, AppHeader...
│       ├── layouts/                  # AppLayout, AuthLayout
│       ├── composables/              # useAppearance, etc.
│       ├── utils/
│       │   ├── http.ts               # Instância axios com interceptor Bearer dinâmico
│       │   ├── notify.js             # Plugin Vue (vue-sonner)
│       │   └── installPWAHelper.js   # Captura evento beforeinstallprompt
│       ├── routes/               # Helpers Wayfinder gerados (não editar manualmente)
│       └── types/                # Tipos TypeScript globais
├── routes/
│   ├── web.php                       # Páginas + auth/token/session
│   ├── api.php                       # API REST (guard: api + sanctum)
│   └── settings.php                  # Rotas de configurações (profile, password, 2FA)
├── tests/
│   ├── TestCase.php                  # withoutMiddleware(ValidateCsrfToken) global
│   ├── Feature/Auth/                 # Authentication, Registration, PasswordReset, 2FA...
│   └── Feature/Settings/             # Profile, Password, TwoFactor
├── docker/app/
│   ├── Dockerfile                    # PHP 8.3 + pdo_mysql + FrankenPHP
│   └── entrypoint.sh                 # composer install, artisan, npm run dev (background)
└── docker-compose.yml              # Serviços: app (8080/5173), db MySQL, redis
```

## Convensões

- **Controllers** renderizam via `Inertia::render('caminho/NomePagina')` — o caminho mapeia direto para `resources/js/pages/`.
- **Rotas nomeadas** são geradas como TypeScript em `resources/js/routes/` pelo Wayfinder — nunca escrever URLs hardcoded no front.
- **Requisições API** usam a instância `http` de `@/utils/http.ts` — já inclui Bearer token dinâmico do `localStorage.api_token`.
- **Testes** usam SQLite in-memory (`DB_DATABASE=:memory:`), session `array`, mail `array`.
