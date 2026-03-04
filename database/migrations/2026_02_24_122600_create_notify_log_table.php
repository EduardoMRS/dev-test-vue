<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notify', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');              // notifiable_type + notifiable_id
            $table->enum('type', ['email', 'push']);   // channel discriminator
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('icon')->nullable();        // push-specific, optional for email
            $table->json('data')->nullable();          // extra fields per channel type
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();  // null = unread, timestamp = when read
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notify');
    }
};
