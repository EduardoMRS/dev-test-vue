<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->comment('email ou push');
            $table->integer('days_before')->comment('Antecedência em dias');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('sent')->comment('sent, failed');
            $table->timestamps();

            // Evitar envio duplicado: mesma tarefa, mesmo canal, mesma antecedência
            $table->unique(['task_id', 'channel', 'days_before'], 'task_notif_unique');
            $table->index(['user_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_notifications');
    }
};
