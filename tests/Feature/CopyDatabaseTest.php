<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CopyDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = sys_get_temp_dir().'/tkj-copy-test-'.uniqid().'.sqlite';
        touch($this->file);

        config(['database.connections.tkj_source' => [
            'driver' => 'sqlite', 'database' => $this->file, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        Artisan::call('migrate', ['--database' => 'tkj_source', '--force' => true]);

        DB::connection('tkj_source')->table('users')->insert([
            'name' => 'Sumber Satu', 'username' => 'sumber1', 'email' => 's1@example.com',
            'password' => 'hash-asli-tidak-berubah', 'role' => User::ROLE_STUDENT, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::connection('tkj_source')->table('gallery_items')->insert([
            'caption' => 'Foto lab', 'image_path' => 'content/gallery/x.jpg', 'sort_order' => 1,
            'is_published' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_copies_rows_and_keeps_source_untouched(): void
    {
        $before = filesize($this->file);

        $this->artisan('tkj:copy-database', ['--source' => $this->file])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['username' => 'sumber1', 'password' => 'hash-asli-tidak-berubah']);
        $this->assertDatabaseHas('gallery_items', ['caption' => 'Foto lab']);
        $this->assertSame(1, DB::connection('tkj_source')->table('users')->count());
        $this->assertSame($before, filesize($this->file));
    }

    public function test_dry_run_copies_nothing(): void
    {
        $this->artisan('tkj:copy-database', ['--source' => $this->file, '--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_refuses_when_target_already_has_data(): void
    {
        $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'sudah.ada']);

        $this->artisan('tkj:copy-database', ['--source' => $this->file])->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['username' => 'sumber1']);
    }

    public function test_missing_source_file_fails_cleanly(): void
    {
        $this->artisan('tkj:copy-database', ['--source' => '/tidak/ada.sqlite'])->assertExitCode(1);
    }
}
