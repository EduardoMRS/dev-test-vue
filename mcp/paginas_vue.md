# Páginas Vue, Layouts e Componentes

## Páginas Inertia (`resources/js/pages/`)

| Arquivo | Rota | Descrição |
|---------|------|----------|
| `Welcome.vue` | `/` | Página inicial pública |
| `dashboard/index.vue` | `/dashboard` | Dashboard autenticado; inicializa Bearer token no `onMounted` |
| `auth/Login.vue` | `/login` | Formulário de login |
| `auth/Register.vue` | `/register` | Cadastro de usuário |
| `auth/ForgotPassword.vue` | `/forgot-password` | Solicita reset de senha |
| `auth/ResetPassword.vue` | `/reset-password/{token}` | Define nova senha |
| `auth/VerifyEmail.vue` | `/email/verify` | Aguardar verificação de email |
| `auth/TwoFactorChallenge.vue` | `/two-factor-challenge` | Código TOTP / recuperação |
| `auth/ConfirmPassword.vue` | `/user/confirm-password` | Reconfirma senha |
| `settings/Profile.vue` | `/settings/profile` | Edição de perfil e exclusão de conta |
| `settings/Password.vue` | `/settings/password` | Alteração de senha |
| `settings/Appearance.vue` | `/settings/appearance` | Tema claro/escuro |
| `settings/TwoFactor.vue` | `/settings/two-factor` | Ativa/desativa 2FA |

## Layouts (`resources/js/layouts/`)

| Layout | Uso |
|--------|-----|
| `AppLayout.vue` | Páginas autenticadas — inclui `AppSidebar` + `AppHeader` |
| `AuthLayout.vue` | Páginas de autenticação — layout centralizado |

## Componentes (`resources/js/components/`)

| Componente | Descrição |
|------------|----------|
| `PushToggle.vue` | Ativa/desativa notificações push Web Push |
| `AppSidebar.vue` | Barra lateral de navegação |
| `AppHeader.vue` | Cabeçalho com menu do usuário |
| `InstallGuideIOS.vue` | Guia de instalação PWA para iOS |
| `PlaceholderPattern.vue` | Placeholder visual |

## Utilities (`resources/js/utils/`)

| Arquivo | Descrição |
|---------|----------|
| `http.ts` | Instância axios com `withCredentials: true` e interceptor Bearer dinâmico |
| `notify.js` | Plugin Vue wrapping vue-sonner |
| `installPWAHelper.js` | Captura `beforeinstallprompt` e expõe `installPWA()` |

## Props compartilhadas (via `HandleInertiaRequests`)

Disponíveis em toda página via `usePage().props`:

```ts
import { usePage } from '@inertiajs/vue3'
const { auth, name, sidebarOpen } = usePage().props
// auth.user — usuário autenticado (null se não logado)
// name — APP_NAME
// sidebarOpen — estado da sidebar
```

## Padrão de uso

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import http from '@/utils/http'   // axios com Bearer automático

// Navegação
// <Link :href="dashboard().url">Dashboard</Link>

// Chamada API
const res = await http.get('/api/notifications/subscriptions')
</script>
```

sobre a contrução das paginas, caso o arquivo comesse a ficar muito grande transforme em uma pasta com os componentes independentes para facil manuntenção
