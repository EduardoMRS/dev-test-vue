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
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('name');
            $table->string('sex')->nullable()->after('birthdate');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('avatar_path');
            $table->string('location')->nullable()->after('bio');
            $table->string('address')->nullable()->after('location');
            $table->string('website')->nullable()->after('sex');
            $table->index('avatar_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['avatar_path']);
            $table->dropColumn(['birthdate', 'sex', 'phone', 'avatar_path', 'bio', 'location', 'address', 'website']);
        });
    }
};
