<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memastikan kolom akun pada tabel users tersedia.
 *
 * Migrasi ini idempotent: setiap kolom/indeks hanya dibuat jika belum ada.
 * Aman dijalankan pada database lama yang sudah berisi data pengguna
 * (kolom yang sudah ada tidak diubah) maupun pada instalasi baru.
 * Tidak ada data yang dihapus; method down() sengaja tidak menghapus apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 30)->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('users', 'display_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('display_name')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 30)->default('student');
            });
        }

        if (! Schema::hasColumn('users', 'photo_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('photo_path')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(false);
            });
        }

        if (! Schema::hasColumn('users', 'two_factor_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('two_factor_enabled')->default(false);
            });
        }

        if (! Schema::hasIndex('users', 'users_role_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role');
            });
        }

        if (! Schema::hasIndex('users', 'users_is_active_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        // Sengaja kosong: migrasi ini tidak boleh menghapus kolom/data akun.
    }
};
