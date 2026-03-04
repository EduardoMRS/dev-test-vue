<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notify extends Model
{
    /** @use HasFactory<\Database\Factories\NotifyFactory> */
    use HasFactory;

    protected $table = 'notify';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'type',
        'title',
        'message',
        'icon',
        'data',
        'status',
        'error_message',
        'sent_at',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The owning notifiable model (e.g. User).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** @param Builder<Notify> $query */
    public function scopeEmail(Builder $query): Builder
    {
        return $query->where('type', 'email');
    }

    /** @param Builder<Notify> $query */
    public function scopePush(Builder $query): Builder
    {
        return $query->where('type', 'push');
    }

    /** @param Builder<Notify> $query */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /** @param Builder<Notify> $query */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /** @param Builder<Notify> $query */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /** @param Builder<Notify> $query */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return false;
        }

        return $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    public function markAsSent(): bool
    {
        return $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markAsFailed(string $error = ''): bool
    {
        return $this->update(['status' => 'failed', 'error_message' => $error]);
    }
}
