<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('link_url')->nullable()->after('body');
        });

        Schema::create('announcement_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index('announcement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_attachments');

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('link_url');
        });
    }
};
