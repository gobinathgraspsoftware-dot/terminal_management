<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 50)->unique();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('vendor_branch_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable()->comment('District');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable()->comment('Assignee');
            $table->unsignedBigInteger('job_type_id')->nullable();
            $table->enum('status', ['open', 'assigned', 'in_progress', 'rescheduled', 'completed', 'closed'])->default('open')->index();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('description');
            $table->integer('sla_hours')->default(24);
            $table->datetime('sla_deadline')->nullable();
            $table->enum('sla_status', ['on_track', 'at_risk', 'breached'])->nullable();
            $table->datetime('assigned_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('closed_at')->nullable();
            $table->datetime('rescheduled_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('vendor_branch_id')->references('id')->on('vendor_branches')->nullOnDelete();
            $table->foreign('state_id')->references('id')->on('states')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('job_type_id')->references('id')->on('job_types')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->text('comment');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('ticket_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_status_history');
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('tickets');
    }
};
