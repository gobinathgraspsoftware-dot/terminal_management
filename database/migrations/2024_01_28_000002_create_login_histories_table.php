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
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable();
            $table->string('device', 100)->nullable();
            $table->enum('login_status', ['success', 'failed'])->default('success');
            $table->string('location', 200)->nullable();
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->integer('session_duration')->nullable()->comment('Duration in minutes');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('login_at');
            $table->index('login_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
