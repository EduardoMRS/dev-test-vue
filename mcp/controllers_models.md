# Controllers e Models

## Controllers

### `Auth/TokenController`
**Propósito:** Emissão e revogação de personal access tokens (Sanctum).

| Método | Rota | Descrição |
|--------|------|----------|
| `issue()` | `POST /api/auth/token` | Autentica via email+senha e retorna Bearer token |
| `issueForSession()` | `POST /auth/token/session` | Emite token para sessão Fortify ativa |
| `revoke()` | `DELETE /api/auth/token` | Revoga o token atual do usuário autenticado |

### `NotificationController`
**Propósito:** Gerenciar push subscriptions e envio de notificações Web Push.

| Método | Rota | Descrição |
|--------|------|----------|
| `subscribe()` | `POST /api/notifications/subscribe` | Registra push subscription do browser |
| `unsubscribe()` | `POST /api/notifications/unsubscribe` | Remove subscription por endpoint |
| `getSubscriptions()` | `GET /api/notifications/subscriptions` | Lista subscriptions do usuário |
| `sendTestNotification()` | `POST /api/notifications/test` | Envia notificação de teste para si mesmo |
| `sendToUser()` | `POST /api/notifications/send-to-user` | Envia para usuário específico |
| `sendToAll()` | `POST /api/notifications/send-to-all` | Broadcast para todos com subscription |
| `getVapidPublicKey()` | `GET /api/notifications/vapid-key` | Retorna chave VAPID pública |

### `Settings/ProfileController`
| Método | Descrição |
|--------|----------|
| `edit(Request)` | Renderiza `settings/Profile.vue` |
| `update(ProfileUpdateRequest)` | Atualiza nome/email; limpa `email_verified_at` se email mudou |
| `destroy(ProfileDeleteRequest)` | Valida senha, faz logout e deleta conta |

### `Settings/PasswordController`
| Método | Descrição |
|--------|----------|
| `edit(Request)` | Renderiza `settings/Password.vue` |
| `update(PasswordUpdateRequest)` | Valida senha atual e atualiza |

### `Settings/TwoFactorAuthenticationController`
| Método | Descrição |
|--------|----------|
| `show(Request)` | Renderiza `settings/TwoFactor.vue` com status 2FA |

---

## Models

### `User`

**Arquivo:** `app/Models/User.php`

**Traits:**
- `HasFactory` — factory para testes
- `Notifiable` — suporte a notificações Laravel
- `TwoFactorAuthenticatable` — campos e métodos 2FA (Fortify)
- `HasApiTokens` — personal access tokens (Sanctum)
- `HasPushSubscriptions` — subscriptions Web Push (laravel-notification-channels/webpush)

**Campos fillable:** `name`, `email`, `password`

**Relacionamentos:**
```php
// Override explícito para apontar para o model local
public function pushSubscriptions() // hasMany(PushSubscription::class)
```

**Métodos:**
```php
public function sendNewNotification(WebPushNotification $notification): void
```

### `PushSubscription`

**Arquivo:** `app/Models/PushSubscription.php`

Estende `NotificationChannels\WebPush\PushSubscription`. Reservado para customizações futuras.

---

## Form Requests

| Arquivo | Validação |
|---------|----------|
| `Settings/ProfileUpdateRequest` | `name` required, `email` required\|email\|unique |
| `Settings/PasswordUpdateRequest` | `current_password`, `password` com regras defaults |
| `Settings/ProfileDeleteRequest` | `password` current_password |

---

## Actions (Fortify)

| Arquivo | Descrição |
|---------|----------|
| `Actions/Fortify/CreateNewUser` | Valida e cria usuário no registro |
| `Actions/Fortify/ResetUserPassword` | Valida e redefine senha |
