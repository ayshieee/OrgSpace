<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->foreignUuid('parent_id')->nullable()->after('organization_id')
                ->constrained('org_files')->cascadeOnDelete();
            $table->boolean('is_folder')->default(false)->after('uploaded_by');
            $table->string('path')->nullable()->change();
            $table->unsignedBigInteger('size')->nullable()->change();

            $table->index(['organization_id', 'parent_id', 'is_folder']);
        });
    }

    public function down(): void
    {
        Schema::table('org_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('is_folder');
            $table->string('path')->nullable(false)->change();
            $table->unsignedBigInteger('size')->nullable(false)->change();
        });
    }
};
