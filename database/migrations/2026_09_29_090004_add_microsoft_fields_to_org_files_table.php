<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->string('ms_drive_item_id')->nullable()->after('preview_status');
            $table->text('ms_web_edit_url')->nullable()->after('ms_drive_item_id');
            $table->timestamp('ms_copy_uploaded_at')->nullable()->after('ms_web_edit_url');
            $table->timestamp('ms_last_pulled_at')->nullable()->after('ms_copy_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->dropColumn(['ms_drive_item_id', 'ms_web_edit_url', 'ms_copy_uploaded_at', 'ms_last_pulled_at']);
        });
    }
};
