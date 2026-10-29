<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_projects')) {
            return;
        }

        Schema::create('student_projects', function (Blueprint $table) {
            $table->id();

            // Pemilik proyek. Proyek ikut terhapus bila akun dihapus.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title', 120);
            $table->text('description');
            $table->json('tech')->nullable();
            $table->string('result', 300)->nullable();
            $table->string('link_url', 255)->nullable();
            $table->string('image_path')->nullable();

            // pending (menunggu), published (tampil), rejected (ditolak)
            $table->string('status', 20)->default('pending');
            $table->string('reject_reason', 300)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'reviewed_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_projects');
    }
};
