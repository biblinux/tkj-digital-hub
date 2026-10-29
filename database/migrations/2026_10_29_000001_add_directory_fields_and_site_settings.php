<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'bio')) {
                $table->string('bio', 200)->nullable();
            }
            if (! Schema::hasColumn('users', 'class_name')) {
                $table->string('class_name', 30)->nullable();
            }
            // Siswa dan teknisi aktif tampil otomatis di situs; pemilik atau admin dapat menyembunyikannya.
            if (! Schema::hasColumn('users', 'show_in_directory')) {
                $table->boolean('show_in_directory')->default(true);
            }
        });

        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 60)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');

        Schema::table('users', function (Blueprint $table) {
            foreach (['bio', 'class_name', 'show_in_directory'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
