<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_types', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default vendor types from existing static values
        DB::table('vendor_types')->insert([
            ['title' => 'Supplier', 'description' => 'Product and material suppliers', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Sub-contractor', 'description' => 'Outsourced service sub-contractors', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Courier', 'description' => 'Delivery and logistics couriers', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Other', 'description' => 'Other vendor types', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_types');
    }
};
