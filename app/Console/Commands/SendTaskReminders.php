<?php

namespace App\Console\Commands;

use App\Models\NotificationSetting;
use App\Models\Task;
use App\Models\TaskNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Envia lembretes de tarefas próximas do vencimento conforme configuração do usuário';

    public function handle(): int
    {
        $this->info('Verificando tarefas próximas do vencimento...');

        // Buscar todas as configurações de notificação
        $settings = NotificationSetting::with('user')->get();

        $sentCount = 0;

        foreach ($settings as $setting) {
            $user = $setting->user;

            if (!$user || !$user->active) {
                continue;
            }

            // Buscar tarefas pendentes do usuário com vencimento dentro da antecedência configurada
            $targetDate = now()->addDays($setting->days_before)->startOfDay();

            $tasks = Task::where('user_id', $user->id)
                ->where('completed', false)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', $targetDate)
                ->whereDate('due_date', '>=', now()->startOfDay())
                ->get();

            foreach ($tasks as $task) {
                // Calcular dias restantes
                $daysUntilDue = (int) now()->startOfDay()->diffInDays($task->due_date, false);

                // Só notificar se estiver dentro da antecedência configurada
                if ($daysUntilDue > $setting->days_before) {
                    continue;
                }

                $channels = [];

                // Verificar se deve enviar por email (configuração global + por tarefa)
                if ($setting->email_enabled && $task->notify_email) {
                    if (!TaskNotification::alreadySent($task->id, 'email', $daysUntilDue)) {
                        $channels[] = 'mail';
                    }
                }

                // Verificar se deve enviar por push (configuração global + por tarefa)
                if ($setting->push_enabled && $task->notify_push) {
                    if (!TaskNotification::alreadySent($task->id, 'push', $daysUntilDue)) {
                        $channels[] = 'push';
                    }
                }

                if (empty($channels)) {
                    continue;
                }

                try {
                    // Enviar notificação
                    $user->notify(new TaskReminderNotification($task, $daysUntilDue, $channels));

                    // Registrar cada canal enviado
                    foreach ($channels as $channel) {
                        $dbChannel = $channel === 'mail' ? 'email' : 'push';
                        TaskNotification::create([
                            'task_id' => $task->id,
                            'user_id' => $user->id,
                            'channel' => $dbChannel,
                            'days_before' => $daysUntilDue,
                            'sent_at' => now(),
                            'status' => 'sent',
                        ]);
                    }

                    $sentCount++;

                    $this->info("Lembrete enviado para {$user->name}: \"{$task->title}\" (vence em {$daysUntilDue} dia(s)) via " . implode(', ', $channels));
                } catch (\Throwable $e) {
                    Log::error("Erro ao enviar lembrete de tarefa #{$task->id} para usuário #{$user->id}: {$e->getMessage()}");

                    // Registrar falha
                    foreach ($channels as $channel) {
                        $dbChannel = $channel === 'mail' ? 'email' : 'push';
                        TaskNotification::create([
                            'task_id' => $task->id,
                            'user_id' => $user->id,
                            'channel' => $dbChannel,
                            'days_before' => $daysUntilDue,
                            'sent_at' => now(),
                            'status' => 'failed',
                        ]);
                    }

                    $this->error("Erro ao enviar lembrete: {$e->getMessage()}");
                }
            }
        }

        $this->info("Total de lembretes enviados: {$sentCount}");

        return self::SUCCESS;
    }
}
