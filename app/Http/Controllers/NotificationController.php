<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Subscribe user to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'sometimes|string|in:aesgcm,aes128gcm',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de subscription inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Usar método nativo da biblioteca
            $subscription = $user->updatePushSubscription(
                $request->input('endpoint'),
                $request->input('keys.p256dh'),
                $request->input('keys.auth'),
                $request->input('contentEncoding', 'aesgcm')
            );

            Log::info('Push subscription created/updated', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'endpoint' => substr($subscription->endpoint, 0, 50) . '...'
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('notification.created'),
                'data' => [
                    'subscription_id' => $subscription->id,
                    'created_at' => $subscription->created_at,
                    'updated_at' => $subscription->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating push subscription', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('notification.create_error')
            ], 500);
        }
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => trans('general.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $endpoint = $request->input('endpoint');

            if ($endpoint) {
                // Usar método nativo para deletar subscription específica
                $user->deletePushSubscription($endpoint);
                $deletedCount = 1;
            } else {
                // Deletar todas as subscriptions do usuário
                $deletedCount = $user->pushSubscriptions()->count();
                $user->pushSubscriptions()->delete();
            }

            Log::info('Push subscription removed', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount,
                'endpoint' => $endpoint ? substr($endpoint, 0, 50) . '...' : 'all'
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('notification.unsubscribed'),
                'data' => [
                    'deleted_count' => $deletedCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing push subscription', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('notification.unsubscribe_error')
            ], 500);
        }
    }

    /**
     * Send notification to specific user (admin only)
     */
    public function sendToUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'url' => 'sometimes|url|nullable',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => trans('general.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $targetUser = User::findOrFail($request->input('user_id'));
            
            // Verificar se o usuário tem subscriptions ativas
            $subscriptionsCount = $targetUser->pushSubscriptions()->count();
            
            if ($subscriptionsCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => trans('notification.user_not_found')
                ], 400);
            }

            $notification = WebPushNotification::createGeneral(
                $request->input('title'),
                $request->input('body'),
                $request->input('url'),
                array_merge($request->input('data', []), [
                    'target_user_id' => $targetUser->id,
                    'sent_by' => Auth::id(),
                    'sent_at' => now()->toISOString()
                ])
            );

            // Enviar notificação via queue
            $targetUser->notify($notification);

            Log::info('Push notification sent to user', [
                'sender_id' => Auth::id(),
                'target_user_id' => $targetUser->id,
                'title' => $request->input('title'),
                'subscriptions_count' => $subscriptionsCount
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('notification.send'),
                'data' => [
                    'target_user' => [
                        'id' => $targetUser->id,
                        'name' => $targetUser->name,
                        'email' => $targetUser->email,
                    ],
                    'title' => $request->input('title'),
                    'body' => $request->input('body'),
                    'subscriptions_count' => $subscriptionsCount,
                    'queued_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending push notification to user', [
                'sender_id' => Auth::id(),
                'target_user_id' => $request->input('user_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('notification.send_error')
            ], 500);
        }
    }

    /**
     * Send notification to all users (super admin only)
     */
    public function sendToAll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'url' => 'sometimes|url|nullable',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar usuários que tem subscriptions ativas
            $usersWithSubscriptions = User::whereHas('pushSubscriptions')->get();
            
            if ($usersWithSubscriptions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => trans('notification.users_not_found')
                ], 400);
            }

            $notification = WebPushNotification::createGeneral(
                $request->input('title'),
                $request->input('body'),
                $request->input('url'),
                array_merge($request->input('data', []), [
                    'broadcast' => true,
                    'sent_by' => Auth::id(),
                    'sent_at' => now()->toISOString()
                ])
            );

            $successCount = 0;
            foreach ($usersWithSubscriptions as $user) {
                try {
                    $user->notify($notification);
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to queue notification for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Broadcast push notification queued', [
                'sender_id' => Auth::id(),
                'title' => $request->input('title'),
                'total_users' => $usersWithSubscriptions->count(),
                'success_count' => $successCount
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('notification.send_broadcast'),
                'data' => [
                    'title' => $request->input('title'),
                    'body' => $request->input('body'),
                    'total_users' => $usersWithSubscriptions->count(),
                    'success_count' => $successCount,
                    'queued_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending broadcast push notification', [
                'sender_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('notification.send_broadcast_error')
            ], 500);
        }
    }

    /**
     * Get user's push subscriptions
     */
    public function getSubscriptions(): JsonResponse
    {
        try {
            $user = Auth::user();
            $subscriptions = $user->pushSubscriptions()->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $subscriptions->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
                        'created_at' => $subscription->created_at,
                        'updated_at' => $subscription->updated_at,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting push subscriptions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('notification.not_found_subscriptions')
            ], 500);
        }
    }

    /**
     * Send a test push notification to the authenticated user.
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->pushSubscriptions()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma subscription ativa encontrada.',
                ], 400);
            }

            $user->notify(WebPushNotification::createGeneral(
                'Notificação de teste',
                'Esta é uma notificação de teste do ' . config('app.name') . '.',
                null,
                ['test' => true, 'sent_at' => now()->toISOString()],
            ));

            return response()->json(['success' => true, 'message' => 'Notificação de teste enviada.']);

        } catch (\Exception $e) {
            Log::error('Test notification failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Erro ao enviar notificação de teste.'], 500);
        }
    }

    /**
     * Get VAPID public key
     */
    public function getVapidPublicKey(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'public_key' => config('webpush.vapid.public_key')
            ]
        ]);
    }
}