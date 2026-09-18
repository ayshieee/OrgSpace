<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->string('preview_path')->nullable()->after('mime_type');
            // native (image/pdf) | converted (via LibreOffice) | failed | unsupported
            $table->string('preview_status')->default('unsupported')->after('preview_path');
        });
    }

    public function down(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->dropColumn(['preview_path', 'preview_status']);
        });
    }
};
