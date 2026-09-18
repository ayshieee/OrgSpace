<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('composer')->nullable();
            $table->string('arranger')->nullable();
            $table->string('section')->nullable();
            $table->string('category')->nullable();
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_restricted')->default(false);
            $table->boolean('is_watermarked')->default(false);
            $table->json('annotations')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('music_favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('music_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['music_entry_id', 'organization_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_favorites');
        Schema::dropIfExists('music_entries');
    }
};
