<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy newsletters/announcements are plain text. Only new
        // Summernote submissions are stored as sanitized HTML.
        Schema::table('broadcast_newsletters', function (Blueprint $table) {
            $table->string('body_format', 12)->default('plain');
        });

        Schema::table('broadcast_announcements', function (Blueprint $table) {
            $table->string('body_format', 12)->default('plain');
            // Existing rows have already been published and retain that state.
            $table->string('status', 20)->default('published')->index();
        });

    }

    public function down(): void
    {
        // Keep the migration safely reversible even when drafts exist:
        // published_at stays nullable rather than fabricating a publication date.
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            $table->dropColumn(['body_format', 'status']);
        });

        Schema::table('broadcast_newsletters', function (Blueprint $table) {
            $table->dropColumn('body_format');
        });
    }
};
