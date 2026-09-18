<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->text('excuse_note')->nullable()->after('marked_by');
            $table->timestamp('excuse_submitted_at')->nullable()->after('excuse_note');
            $table->string('excuse_status')->nullable()->after('excuse_submitted_at'); // pending|approved|denied
            $table->foreignUuid('excuse_reviewed_by')->nullable()->after('excuse_status')->constrained('users')->nullOnDelete();
            $table->timestamp('excuse_reviewed_at')->nullable()->after('excuse_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['excuse_reviewed_by']);
            $table->dropColumn(['excuse_note', 'excuse_submitted_at', 'excuse_status', 'excuse_reviewed_by', 'excuse_reviewed_at']);
        });
    }
};
