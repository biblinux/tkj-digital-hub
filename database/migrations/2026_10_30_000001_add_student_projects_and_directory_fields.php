<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proyek siswa (dikirim siswa, ditinjau admin) dan data direktori siswa.
 * Hanya menambah tabel dan kolom; aman untuk database yang sudah berisi data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_projects')) {
            Schema::create('student_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 120);
                $table->text('description');
                $table->string('technologies', 200)->nullable();
                $table->string('result', 300)->nullable();
                $table->string('link_url', 255)->nullable();
                $table->string('image_path')->nullable();
                $table->string('status', 12)->default('pending');
                $table->string('review_note', 300)->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'class_name')) {
                $table->string('class_name', 30)->nullable();
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->string('bio', 300)->nullable();
            }
            if (! Schema::hasColumn('users', 'show_in_directory')) {
                $table->boolean('show_in_directory')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_projects');
        Schema::table('users', function (Blueprint $table) {
            foreach (['class_name', 'bio', 'show_in_directory'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
