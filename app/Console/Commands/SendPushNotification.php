<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Console\Command;

class SendPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:send
                            {--user_id= : ID do usuário para enviar (opcional)}
                            {--all : Enviar para todos os usuários}
                            {--title= : Título da notificação}
                            {--body= : Corpo da notificação}
                            {--url= : URL para abrir quando clicar (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar notificação push para usuários';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Validar parâmetros obrigatórios
        $title = $this->option('title');
        $body = $this->option('body');

        if (!$title || !$body) {
            $this->error('Título e corpo da notificação são obrigatórios.');
            $this->info('Uso: php artisan push:send --title="Título" --body="Mensagem" [--user_id=1] [--all] [--url="https://exemplo.com"]');
            return 1;
        }

        // Determinar destinatários
        $users = $this->getTargetUsers();
        
        if ($users->isEmpty()) {
            $this->error('Nenhum usuário encontrado para envio.');
            return 1;
        }

        // Confirmar envio
        $userCount = $users->count();
        $this->info("Preparando para enviar notificação para {$userCount} usuário(s):");
        $this->info("Título: {$title}");
        $this->info("Corpo: {$body}");
        
        if ($this->option('url')) {
            $this->info("URL: " . $this->option('url'));
        }

        if (!$this->confirm('Deseja continuar?')) {
            $this->info('Envio cancelado.');
            return 0;
        }

        // Criar notificação
        $notification = WebPushNotification::createGeneral(
            $title,
            $body,
            $this->option('url'),
            [
                'sent_via' => 'artisan_command',
                'sent_at' => now()->toISOString(),
                'command_user' => env('USER', 'system')
            ]
        );

        // Enviar notificações
        $successCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($userCount);
        $progressBar->start();

        foreach ($users as $user) {
            try {
                // Verificar se o usuário tem subscriptions ativas
                if ($user->pushSubscriptions()->count() === 0) {
                    $this->newLine();
                    $this->warn("Usuário {$user->name} (ID: {$user->id}) não possui subscriptions ativas.");
                    $progressBar->advance();
                    continue;
                }

                $user->notify($notification);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("Erro ao enviar para usuário {$user->name} (ID: {$user->id}): " . $e->getMessage());
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Exibir resultados
        $this->info("Envio concluído!");
        $this->info("✅ Enviadas com sucesso: {$successCount}");
        
        if ($errorCount > 0) {
            $this->warn("❌ Erros: {$errorCount}");
        }

        $this->info("📋 Total de usuários processados: {$userCount}");
        $this->info("🚀 As notificações foram adicionadas à fila e serão processadas em breve.");

        return 0;
    }

    /**
     * Obter usuários alvos baseado nos parâmetros
     */
    private function getTargetUsers()
    {
        if ($this->option('all')) {
            // Enviar para todos os usuários que têm subscriptions
            return User::whereHas('pushSubscriptions')->get();
        }

        if ($userId = $this->option('user_id')) {
            // Enviar para usuário específico
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("Usuário com ID {$userId} não encontrado.");
                return collect();
            }

            return collect([$user]);
        }

        // Se não especificou --all nem --user_id, perguntar
        $choice = $this->choice(
            'Para quem deseja enviar a notificação?',
            ['Usuário específico', 'Todos os usuários'],
            0
        );

        if ($choice === 'Todos os usuários') {
            return User::whereHas('pushSubscriptions')->get();
        } else {
            $userId = $this->ask('Digite o ID do usuário:');
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("Usuário com ID {$userId} não encontrado.");
                return collect();
            }

            return collect([$user]);
        }
    }
}
