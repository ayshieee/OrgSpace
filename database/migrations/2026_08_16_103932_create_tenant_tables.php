<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Core Organizations Table
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('plan_type')->default('free');
            $table->json('settings')->nullable();
            $table->json('branding')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Organization Members (Links Users to Organizations)
        Schema::create('organization_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('membership_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        // 3. Custom Tenant-Scoped Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        // 4. Pivot Table for Assigning Roles to Members
        Schema::create('organization_member_role', function (Blueprint $table) {
            $table->foreignUuid('organization_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['organization_member_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_member_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organizations');
    }
};