<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name', 150);
            $table->string('postcode', 10);
            $table->timestamps();

            $table->index('postcode');
            $table->index('name');
            $table->index(['state_id', 'name']);
            $table->index(['state_id', 'postcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
