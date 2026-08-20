<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('requirement_code')->nullable()->after('original_name');
            $table->index('requirement_code');
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex(['requirement_code']);
            $table->dropColumn('requirement_code');
        });
    }
};
