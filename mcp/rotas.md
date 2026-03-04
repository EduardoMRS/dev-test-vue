# Rotas do Projeto

## Rotas Web (`routes/web.php` + `routes/settings.php`)

Carregadas sob o middleware group `web` (sessão, CSRF, cookies).

```
GET  /                        home                   Welcome.vue
GET  /dashboard               dashboard              Dashboard.vue   [auth, verified]
POST /auth/token/session      auth.token.session     TokenController@issueForSession  [auth]
GET  /login                   login                  auth/Login.vue
POST /login                   login.store            Fortify
POST /logout                  logout                 Fortify
GET  /register                register               auth/Register.vue
POST /register                register.store         Fortify
GET  /forgot-password         password.request       auth/ForgotPassword.vue
POST /forgot-password         password.email         Fortify
GET  /reset-password/{token}  password.reset         auth/ResetPassword.vue
POST /reset-password          password.update        Fortify
GET  /email/verify            verification.notice    auth/VerifyEmail.vue
GET  /email/verify/{id}/{hash} verification.verify   Fortify
POST /email/verification-notification verification.send Fortify
GET  /settings/profile        profile.edit           settings/Profile.vue  [auth]
PATCH /settings/profile       profile.update         ProfileController  [auth]
DELETE /settings/profile      profile.destroy        ProfileController  [auth, verified]
GET  /settings/password       user-password.edit     settings/Password.vue  [auth, verified]
PUT  /settings/password       user-password.update   PasswordController  [auth, verified]
GET  /settings/appearance     appearance.edit        settings/Appearance.vue  [auth, verified]
GET  /settings/two-factor     two-factor.show        settings/TwoFactor.vue  [auth, verified]
```

## Rotas API (`routes/api.php`)

Carregadas sob o middleware group `api` (sem CSRF, throttle 60/min, prefix `/api/`).

```
# Públicas
GET    /api/notifications/vapid-key    NotificationController@getVapidPublicKey
POST   /api/auth/token                 TokenController@issue  (email+password)

# Autenticadas [auth:sanctum] — requer Bearer token
DELETE /api/auth/token                 TokenController@revoke
POST   /api/notifications/subscribe    NotificationController@subscribe
POST   /api/notifications/unsubscribe  NotificationController@unsubscribe
GET    /api/notifications/subscriptions NotificationController@getSubscriptions
POST   /api/notifications/test         NotificationController@sendTestNotification
POST   /api/notifications/send-to-user NotificationController@sendToUser
POST   /api/notifications/send-to-all  NotificationController@sendToAll
```

## Helpers Wayfinder (frontend)

Gerados automaticamente em `resources/js/routes/` e `resources/js/actions/`.

```ts
import { dashboard, login, register, home } from '@/routes'
import login from '@/routes/login'          // login.store.post()
import profile from '@/routes/settings/profile' // profile.update.patch()

// Uso em templates
<Link :href="dashboard().url">Dashboard</Link>

// Uso em forms (Inertia useForm)
const form = useForm({...})
form.post(login.store.url())
```

> **Não escrever URLs hardcoded no front** — usar sempre os helpers Wayfinder.
