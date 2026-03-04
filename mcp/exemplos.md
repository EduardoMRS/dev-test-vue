# Exemplos de Uso e Padrões

## Adicionar uma nova página autenticada

### 1. Rota (`routes/web.php`)
```php
Route::get('relatorios', [RelatorioController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('relatorios.index');
```

### 2. Controller
```php
public function index(Request $request): \Inertia\Response
{
    return Inertia::render('Relatorios/Index', [
        'dados' => Relatorio::where('user_id', $request->user()->id)->get(),
    ]);
}
```

### 3. Página Vue (`resources/js/pages/Relatorios/Index.vue`)
```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
const props = defineProps<{ dados: any[] }>()
</script>
<template>
  <Head title="Relatórios" />
  <AppLayout><!-- conteúdo --></AppLayout>
</template>
```

### 4. Regenerar helpers Wayfinder
```bash
./art php artisan wayfinder:generate
```

---

## Adicionar uma nova rota API (Bearer)

```php
// routes/api.php — dentro do grupo auth:sanctum
Route::get('relatorios', [RelatorioApiController::class, 'index']);
```

```ts
// No front-end (axios com Bearer automático)
import http from '@/utils/http'
const res = await http.get('/api/relatorios')
```

---

## Enviar notificação push

```php
$user->notify(WebPushNotification::createGeneral(
    title: 'Novo relatório disponível',
    body: 'Seu relatório mensal foi gerado.',
    url: '/relatorios',
    data: ['relatorio_id' => 42],
));
```

---

## Obter Bearer token (fluxo SPA)

```ts
// Automático no Dashboard.vue (após login Fortify):
const res = await http.post('/auth/token/session')
localStorage.setItem('api_token', res.data.token)

// Via credenciais (app externo):
const res = await axios.post('/api/auth/token', { email, password })
// res.data.token — Bearer token
```

---

## Executar testes

```bash
./art php artisan test --no-coverage
# Resultado esperado: 41 passed

./art php artisan test --filter AuthenticationTest
```

---

## Comandos úteis

```bash
./art migrate                              # rodar migrations
./art php artisan route:list               # listar rotas
./art php artisan wayfinder:generate       # regenerar helpers TypeScript
./art npm run build                        # compilar front-end
docker compose logs -f app                 # logs do container
./art mysql laravel                        # acessar banco
```
