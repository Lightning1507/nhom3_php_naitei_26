<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('service_categories')
                ->restrictOnDelete();
            $table->foreignId('responsible_department_id')
                ->constrained('departments')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->json('form_schema')->nullable();
            $table->json('document_requirements')->nullable();
            $table->unsignedInteger('processing_time_days')->nullable();
            $table->decimal('fee', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('responsible_department_id');
        });

        Schema::create('service_staff', function (Blueprint $table) {
            $table->foreignId('service_type_id')
                ->constrained('service_types')
                ->cascadeOnDelete();
            $table->foreignId('staff_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_type_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_staff');
        Schema::dropIfExists('service_types');
    }
};
