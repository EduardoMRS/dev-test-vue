<?php

namespace App\Notifications;

use App\Models\Notify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendMessageMail extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var list<string> */
    protected array $attachments;

    /**
     * @param string      $subject     E-mail subject
     * @param string|null $to          Recipient; defaults to mail.from.address
     * @param string      $message     HTML or plain-text body
     * @param string      $from        Sender e-mail address
     * @param list<string> $attachments Absolute file paths to attach
     */
    public function __construct(
        public readonly string $subject,
        public readonly string $message,
        public readonly ?string $to = null,
        public readonly ?string $from = null,
        array $attachments = [],
    ) {
        $this->attachments = $attachments;
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Convenience factory — mirrors the WebPushNotification pattern.
     *
     * Usage:
     *   SendMessageMail::create('Subject', 'from@example.com', '<p>Body</p>')
     *       ->withAttachments('/path/file.pdf')
     *       ->dispatch();
     */
    public static function create(
        string $subject,
        string $message,
        ?string $to = null,
        ?string $from = null,
        array $attachments = [],
    ): static {
        return new static($subject, $message, $to, $from, $attachments);
    }

    // -------------------------------------------------------------------------
    // Fluent helpers
    // -------------------------------------------------------------------------

    /**
     * Append one or more absolute file paths as attachments.
     *
     * @param  string ...$paths
     * @return static
     */
    public function withAttachments(string ...$paths): static
    {
        $this->attachments = [...$this->attachments, ...$paths];
        return $this;
    }

    // -------------------------------------------------------------------------
    // Dispatch helper
    // -------------------------------------------------------------------------

    /**
     * Queue this notification to the resolved recipient.
     *
     * When $to is null the application's default mail.from.address is used.
     */
    public function dispatch()
    {
        $recipient = $this->to ?? config('mail.from.address');

        Notify::create([
            'notifiable_type' => self::class,
            'notifiable_id' => 0, // No specific user association
            'type' => 'email',
            'title' => $this->subject,
            'message' => $this->message,
            'status' => 'sent',
            'data' => json_encode([
                'from' => $this->from,
                'to' => $recipient,
                'attachments' => $this->attachments,
            ]),
        ]);

        // TODO: corrigir retorno para não voltar null do NotificationFacade::route()
        return NotificationFacade::route('mail', $recipient)->notify($this);
    }

    // -------------------------------------------------------------------------
    // Notification contract
    // -------------------------------------------------------------------------

    public function via($_notifiable): array
    {
        return ['mail'];
    }

    public function toMail($_notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->subject)
            ->from($this->from ?? config('mail.from.address'))
            ->view('email.raw', ['body' => $this->message, 'title' => $this->subject]);

        foreach ($this->attachments as $path) {
            $mail->attach($path);
        }

        return $mail;
    }

    /**
     * Data exposed when the notification is stored in the database
     * (useful if 'database' is ever added to via()).
     *
     * @return array<string, mixed>
     */
    public function toArray($_notifiable): array
    {
        return [
            'subject'     => $this->subject,
            'from'        => $this->from,
            'to'          => $this->to,
            'attachments' => $this->attachments,
        ];
    }
}
