<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('type')->nullable()->after('plan_type');
            $table->string('status')->default('draft')->after('type');
            $table->string('onboarding_step')->default('profile')->after('status');
            $table->timestamp('activated_at')->nullable()->after('onboarding_step');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['type', 'status', 'onboarding_step', 'activated_at']);
        });
    }
};
