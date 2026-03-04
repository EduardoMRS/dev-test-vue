<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class WebPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string,mixed>|null $data Additional payload data for the notification
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?array $data = null,
        public ?string $icon = null,
        public ?string $badge = null,
        public ?string $tag = null,
        public bool $requireInteraction = false
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $message = (new WebPushMessage())
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon ?? '/favicon.ico')
            ->badge($this->badge ?? '/favicon.ico')
            ->tag($this->tag ?? 'default')
            ->requireInteraction($this->requireInteraction);

        if (!empty($this->data)) {
            $message->data($this->data);
        }

        if ($this->url) {
            $message->action('Abrir', $this->url);
        }

        return $message;
    }

    /**
     * Factory helper used across the app to create a standard notification.
     *
     * Usage: WebPushNotification::createGeneral($title, $body, $url = null, $data = [], $icon = null, $badge = null, $tag = null, $requireInteraction = false)
     *
     * @param string $title
     * @param string $body
     * @param string|null $url
     * @param array|null $data
     * @param string|null $icon
     * @param string|null $badge
     * @param string|null $tag
     * @param bool $requireInteraction
     * @return static
     */
    public static function createGeneral(
        string $title,
        string $body,
        ?string $url = null,
        ?array $data = null,
        ?string $icon = null,
        ?string $badge = null,
        ?string $tag = null,
        bool $requireInteraction = false
    ): static {
        return new static($title, $body, $url, $data, $icon, $badge, $tag, $requireInteraction);
    }
}
