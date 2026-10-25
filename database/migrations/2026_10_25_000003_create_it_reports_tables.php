<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_reports', function (Blueprint $table) {
            $table->id();

            // Nomor tiket yang mudah dibaca, diisi setelah baris dibuat.
            $table->string('reference', 20)->nullable()->unique();

            // Pelapor. Laporan tidak ikut terhapus bila akun dihapus.
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Teknisi yang ditugaskan.
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // network, hardware, software, account, lab, other
            $table->string('category', 30);
            $table->string('title', 150);
            $table->text('description');
            $table->string('location', 150)->nullable();

            // low, normal, high, urgent
            $table->string('urgency', 10)->default('normal');

            // open, assigned, in_progress, resolved, closed, rejected
            $table->string('status', 20)->default('open');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('urgency');
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('it_report_updates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('it_report_id')
                ->constrained('it_reports')
                ->cascadeOnDelete();

            // Pelaku perubahan.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->text('note')->nullable();

            // Catatan internal hanya terlihat oleh teknisi/admin.
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index(['it_report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_report_updates');
        Schema::dropIfExists('it_reports');
    }
};
