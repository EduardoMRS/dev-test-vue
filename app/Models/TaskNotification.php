<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskNotification extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'channel',
        'days_before',
        'sent_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'days_before' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Verifica se já foi enviada notificação para esta tarefa, canal e antecedência.
     */
    public static function alreadySent(int $taskId, string $channel, int $daysBefore): bool
    {
        return static::where('task_id', $taskId)
            ->where('channel', $channel)
            ->where('days_before', $daysBefore)
            ->exists();
    }
}
