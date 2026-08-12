<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->restrictOnDelete();
            $table->foreignId('staff_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();
            $table->foreignId('assigned_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'assigned_at']);
            $table->index('staff_id');
            $table->index('assigned_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_assignments');
    }
};
