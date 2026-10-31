<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berita dan galeri kiriman pengguna perlu persetujuan admin. Baris yang sudah
 * ada (dibuat admin) otomatis berstatus "approved", jadi tampilan tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['news_posts', 'gallery_items'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (! Schema::hasColumn($name, 'review_status')) {
                    $table->string('review_status', 12)->default('approved');
                }
                if (! Schema::hasColumn($name, 'review_note')) {
                    $table->string('review_note', 300)->nullable();
                }
                if (! Schema::hasColumn($name, 'submitted_by')) {
                    $table->foreignId('submitted_by')->nullable()->constrained('users')->cascadeOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['news_posts', 'gallery_items'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (Schema::hasColumn($name, 'submitted_by')) {
                    $table->dropConstrainedForeignId('submitted_by');
                }
                foreach (['review_status', 'review_note'] as $col) {
                    if (Schema::hasColumn($name, $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
