<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->boolean('is_restricted')->default(false)->after('mime_type');
            $table->boolean('is_watermarked')->default(false)->after('is_restricted');
        });
    }

    public function down(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->dropColumn(['is_restricted', 'is_watermarked']);
        });
    }
};
