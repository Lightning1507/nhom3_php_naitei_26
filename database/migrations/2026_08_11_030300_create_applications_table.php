<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_code')->unique();
            $table->foreignId('citizen_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('service_type_id')
                ->constrained('service_types')
                ->restrictOnDelete();
            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status')->default('received');
            $table->json('form_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('result_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('citizen_id');
            $table->index('service_type_id');
            $table->index('assigned_staff_id');
            $table->index('status');
            $table->index('submitted_at');
            $table->index(['citizen_id', 'submitted_at']);
            $table->index(['status', 'service_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
