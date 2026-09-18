<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_comment_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('announcement_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // 'image' | 'file' | 'link'
            $table->string('name'); // original filename, or the link's display text
            $table->string('path')->nullable(); // storage path; null when type = 'link'
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // null when type = 'link'
            $table->string('url')->nullable(); // external URL; only set when type = 'link'
            $table->timestamps();

            $table->index('announcement_comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_comment_attachments');
    }
};
