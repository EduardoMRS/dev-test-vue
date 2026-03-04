<?php

use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estas rotas são carregadas sob o middleware group 'api', que NÃO inclui
| CSRF. Autenticação ocorre via Bearer token (Sanctum HasApiTokens).
|
| Para obter um token, o front-end deve:
|   1. Fazer login pelo Fortify (POST /login) — estabelece sessão web
|   2. Chamar POST /auth/token/session — retorna Bearer token para a sessão
|   3. Ou chamar POST /api/auth/token — com email+password (uso externo/SPA)
*/

// ─── Rotas públicas ───────────────────────────────────────────────────────────

// Chave pública VAPID para registro de push subscriptions no front-end
Route::get('notifications/vapid-key', [NotificationController::class, 'getVapidPublicKey']);

// Emitir token via credenciais (ex.: apps nativos, testes externos)
Route::post('auth/token', [TokenController::class, 'issue']);

// ─── Rotas autenticadas (Bearer token via Sanctum) ────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Revogar token atual
    Route::delete('auth/token', [TokenController::class, 'revoke']);

    // Push notifications
    Route::prefix('notifications')->group(function () {
        Route::post('subscribe', [NotificationController::class, 'subscribe']);
        Route::post('unsubscribe', [NotificationController::class, 'unsubscribe']);
        Route::get('subscriptions', [NotificationController::class, 'getSubscriptions']);
        Route::post('test', [NotificationController::class, 'sendTestNotification']);

        // Envio direcionado -- adicionar middleware de role quando implementado
        Route::post('send-to-user', [NotificationController::class, 'sendToUser']);
        Route::post('send-to-all', [NotificationController::class, 'sendToAll']);
    });
});
