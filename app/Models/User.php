<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Notify;
use App\Models\Task;
use App\Models\NotificationSetting;
use App\Notifications\SendMessageMail;
use App\Notifications\WebPushNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasApiTokens, HasPushSubscriptions;

    /**
     * Possible user roles.
     */
    const ROLE_USER      = 'user';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_ADMIN     = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'phone',
        'birthdate',
        'sex',
        'bio',
        'location',
        'address',
        'website',
        'avatar_path',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Role helpers
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function assignRole(string $role): void
    {
        if (in_array($role, [self::ROLE_USER, self::ROLE_MODERATOR, self::ROLE_ADMIN])) {
            $this->role = $role;
            $this->save();
        }
    }

    /**
     * Returns true if the user has at least moderator-level access.
     */
    public function isAtLeastModerator(): bool
    {
        return in_array($this->role, [self::ROLE_MODERATOR, self::ROLE_ADMIN]);
    }

    /**
     * Envia uma notificação push ao usuário.
     *
     * @param WebPushNotification|array{0:string,1:string,2?:string|null,3?:array|null,4?:string|null,5?:string|null,6?:string|null,7?:bool} $notification
     *   Instância de WebPushNotification ou array posicional compatível com
     *   WebPushNotification::createGeneral($title, $body, ...).
     *
     * @throws \InvalidArgumentException
     */
    public function sendPush(WebPushNotification|array $notification)
    {
        if (is_array($notification)) {
            if (count($notification) < 2) {
                throw new \InvalidArgumentException(
                    'Array de notificação deve conter ao menos [title, body].'
                );
            }
            $notification = WebPushNotification::createGeneral(...array_values($notification));
        }

        $this->notify($notification);
        return true;
    }

    public function sendMail(string $subject, string $message, ?string $from = null, ?array $attachments = [])
    {
        $recipient = filter_var($this->email, FILTER_VALIDATE_EMAIL) ? $this->email : null;

        if (is_null($recipient)) {
            Log::warning('Attempted to send mail but user email is invalid', [
                'user_id' => $this->id,
                'email' => $this->email,
                'subject' => $subject,
            ]);

            return false;
        }

        SendMessageMail::create(
            $subject,
            $message,
            $recipient,
            $from,
            $attachments)
            ->dispatch();
        return true;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * All notification log entries addressed to this user.
     *
     * Usage:
     *   $user->notifyLogs()->unread()->get();
     *   $user->notifyLogs()->email()->latest()->paginate();
     *   $user->notifyLogs()->push()->sent()->count();
     */
    public function notifyLogs(): MorphMany
    {
        return $this->morphMany(Notify::class, 'notifiable');
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function notificationSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    /**
     * Retorna as configurações de notificação do usuário, criando padrão se não existir.
     */
    public function getOrCreateNotificationSetting(): NotificationSetting
    {
        return $this->notificationSetting ?? $this->notificationSetting()->create([
            'days_before' => 1,
            'email_enabled' => true,
            'push_enabled' => true,
        ]);
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->birthdate) {
            return null;
        }
        return now()->diffInYears($this->birthdate);
    }

    /**
     * Retorna o conteúdo binário da foto de perfil do usuário
     * @return bool|string|null
     */
    public function getProfilePhotoAttribute()
    { 
        if ($this->avatar_path) {
            $path = storage_path("app/public/{$this->avatar_path}");
            if (file_exists($path)) {
                    return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
            }
        }
        return response()->json(null, 404);
    }

    /**
     * Realiza o salvamento de uma imagem de perfil para o usuário
     * @param mixed $value
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setProfilePhotoAttribute($value)
    {
        if (is_null($value)) {
            if ($this->avatar_path) {
                $path = storage_path("app/public/{$this->avatar_path}");
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            $this->attributes['avatar_path'] = null;
        } elseif ($value instanceof \Illuminate\Http\UploadedFile && $value->isValid()) {
            if ($this->avatar_path) {
                $old = storage_path("app/public/{$this->avatar_path}");
                if (file_exists($old)) {
                    @unlink($old);
                }
            }
            $filename = time() . '_' . uniqid() . '.' . $value->getClientOriginalExtension();
            $path = $value->storeAs('public/avatars', $filename);
            $this->attributes['avatar_path'] = str_replace('public/', '', $path);
        } else {
            throw new \InvalidArgumentException('Valor inválido para profile_photo. Deve ser um arquivo de imagem válido ou null.');
        }
    }
}
