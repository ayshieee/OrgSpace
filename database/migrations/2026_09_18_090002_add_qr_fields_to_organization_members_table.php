<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique()->after('membership_number');
            $table->unsignedInteger('qr_version')->default(1)->after('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'qr_version']);
        });
    }
};
