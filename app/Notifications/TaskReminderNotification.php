<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly int $daysBefore,
        public readonly array $channels = ['mail'],
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $via = [];

        if (in_array('mail', $this->channels)) {
            $via[] = 'mail';
        }

        if (in_array('push', $this->channels)) {
            $via[] = WebPushChannel::class;
        }

        return $via;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->task->due_date->format('d/m/Y');

        return (new MailMessage())
            ->subject("Lembrete: Tarefa \"{$this->task->title}\" vence em {$this->daysBefore} dia(s)")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Sua tarefa \"{$this->task->title}\" vence em **{$dueDate}**.")
            ->line("Faltam **{$this->daysBefore} dia(s)** para o vencimento.")
            ->when($this->task->description, function ($mail) {
                return $mail->line("Descrição: {$this->task->description}");
            })
            ->action('Ver Tarefas', url('/tasks'))
            ->line('Não se esqueça de concluí-la a tempo!');
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage())
            ->title("Tarefa vence em {$this->daysBefore} dia(s)")
            ->body("A tarefa \"{$this->task->title}\" vence em {$this->task->due_date->format('d/m/Y')}.")
            ->icon('/favicon.ico')
            ->badge('/favicon.ico')
            ->tag("task-reminder-{$this->task->id}-{$this->daysBefore}")
            ->action('Ver Tarefas', '/tasks')
            ->requireInteraction(true);
    }
}
