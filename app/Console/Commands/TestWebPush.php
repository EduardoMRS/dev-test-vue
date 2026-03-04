<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Console\Command;

class TestWebPush extends Command
{
    protected $signature = 'test:webpush {--user_id=1}';
    protected $description = 'Test Web Push notification system';

    public function handle()
    {
        $this->info('🧪 Testando sistema de Web Push...');
        
        // Verificar extensões
        $this->info('📦 Verificando extensões PHP:');
        $this->info('   GMP: ' . (extension_loaded('gmp') ? '✅ OK' : '❌ MISSING'));
        $this->info('   BCMath: ' . (extension_loaded('bcmath') ? '✅ OK' : '❌ MISSING'));
        
        // Verificar pacote
        $this->info('📚 Verificando pacote WebPush:');
        $webpushOk = class_exists('NotificationChannels\WebPush\WebPushServiceProvider');
        $this->info('   WebPush Package: ' . ($webpushOk ? '✅ OK' : '❌ MISSING'));
        
        // Verificar configurações
        $this->info('🔧 Verificando configurações VAPID:');
        $vapidPublic = config('webpush.vapid.public_key');
        $vapidPrivate = config('webpush.vapid.private_key');
        $this->info('   Public Key: ' . ($vapidPublic ? '✅ Configurada' : '❌ Missing'));
        $this->info('   Private Key: ' . ($vapidPrivate ? '✅ Configurada' : '❌ Missing'));
        
        if (!$webpushOk || !$vapidPublic || !$vapidPrivate) {
            $this->error('❌ Sistema não está configurado corretamente!');
            return 1;
        }
        
        // Buscar usuário
        $userId = $this->option('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuário com ID {$userId} não encontrado!");
            
            // Mostrar usuários disponíveis
            $users = User::take(5)->get(['id', 'name', 'email']);
            if ($users->count() > 0) {
                $this->info('📋 Usuários disponíveis:');
                foreach ($users as $u) {
                    $this->info("   ID: {$u->id} - {$u->name} ({$u->email})");
                }
            } else {
                $this->info('📋 Nenhum usuário encontrado no sistema.');
            }
            return 1;
        }
        
        $this->info("👤 Testando com usuário: {$user->name} ({$user->email})");
        
        // Verificar subscriptions
        $subscriptions = $user->pushSubscriptions()->count();
        $this->info("📱 Subscriptions ativas: {$subscriptions}");
        
        if ($subscriptions === 0) {
            $this->warn('⚠️  Usuário não possui subscriptions ativas.');
            $this->info('💡 Para receber notificações, o usuário precisa:');
            $this->info('   1. Acessar o site');
            $this->info('   2. Permitir notificações no browser');
            $this->info('   3. Clicar em "Ativar Notificações"');
            
            // Mesmo assim, vamos tentar enviar
            $this->info('🚀 Tentando enviar notificação mesmo assim...');
        }
        
        try {
            // Criar notificação de teste usando a sintaxe correta
            $notification = new WebPushNotification(
                title: '🎉 Sistema Funcionando!',
                body: 'Notificação enviada com sucesso após instalação das extensões PHP GMP e BCMath!',
                url: url('/'),
                icon: '/favicon.ico',
                tag: 'test'
            );
            
            $this->info('📤 Enviando notificação...');
            $user->notify($notification);
            
            $this->info('✅ Notificação enviada para o queue com sucesso!');
            $this->info('🔄 Verifique o queue worker para confirmar o processamento.');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Erro ao enviar notificação:');
            $this->error('   ' . $e->getMessage());
            return 1;
        }
    }
}