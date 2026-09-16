<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('activated_at');
            $table->string('join_code')->nullable()->unique()->after('is_public');
            $table->index(['is_public', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'status']);
            $table->dropColumn(['is_public', 'join_code']);
        });
    }
};
