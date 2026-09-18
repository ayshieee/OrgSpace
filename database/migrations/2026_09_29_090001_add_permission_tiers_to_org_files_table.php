<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            // null = any active member (view) / manage_files holders only (edit) —
            // non-null values are permission keys from config('permissions.keys').
            $table->string('view_permission')->nullable()->after('is_watermarked');
            $table->string('edit_permission')->nullable()->after('view_permission');
        });
    }

    public function down(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->dropColumn(['view_permission', 'edit_permission']);
        });
    }
};
