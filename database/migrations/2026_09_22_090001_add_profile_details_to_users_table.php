<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('avatar_path');
            $table->string('pronouns')->nullable()->after('bio');
            $table->string('phone_number')->nullable()->after('pronouns');
            $table->string('location')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'pronouns', 'phone_number', 'location']);
        });
    }
};
