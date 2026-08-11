<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->restrictOnDelete();
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('document_kind');
            $table->string('original_name');
            $table->string('disk');
            $table->text('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('application_id');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
