<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konten website yang dikelola dari dashboard: berita, galeri, fasilitas,
 * dan struktur organisasi/tim IT. Hanya menambah tabel; aman untuk database
 * yang sudah berisi data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_posts')) {
            Schema::create('news_posts', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->string('category', 40);
                $table->string('summary', 300);
                $table->text('body')->nullable();
                $table->string('image_path')->nullable();
                $table->string('status', 12)->default('draft');
                $table->date('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'published_at']);
            });
        }

        if (! Schema::hasTable('gallery_items')) {
            Schema::create('gallery_items', function (Blueprint $table) {
                $table->id();
                $table->string('caption', 150);
                $table->string('image_path');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_published')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_published', 'sort_order']);
            });
        }

        if (! Schema::hasTable('facilities')) {
            Schema::create('facilities', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->text('description');
                $table->string('image_path')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_published')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_published', 'sort_order']);
            });
        }

        if (! Schema::hasTable('org_members')) {
            Schema::create('org_members', function (Blueprint $table) {
                $table->id();
                $table->string('group', 20);
                $table->string('name', 100);
                $table->string('position', 100);
                $table->text('tasks')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_published')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['group', 'is_published', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('org_members');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('news_posts');
    }
};
