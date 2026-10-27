<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyalin data dari berkas SQLite ke database aktif (misalnya MariaDB).
 *
 * Aman dijalankan: berkas SQLite hanya dibaca, tidak diubah atau dihapus;
 * perintah menolak berjalan bila tabel data di tujuan sudah berisi baris,
 * sehingga tidak pernah menimpa data yang ada. Jalankan `php artisan migrate`
 * pada database tujuan lebih dulu.
 */
class CopyDatabase extends Command
{
    protected $signature = 'tkj:copy-database
        {--source=database/database.sqlite : Berkas SQLite sumber (hanya dibaca)}
        {--dry-run : Tampilkan jumlah baris saja, tanpa menyalin}';

    protected $description = 'Salin semua data dari berkas SQLite ke database aktif (tujuan harus kosong)';

    /** Tabel sementara yang tidak perlu dipindahkan. */
    private const SKIP = ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'];

    private const CHUNK = 500;

    public function handle(): int
    {
        $path = (string) $this->option('source');
        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            $this->error('Berkas sumber tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        config(['database.connections.tkj_source' => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('tkj_source');

        $targetName = DB::getDefaultConnection();
        $target = DB::connection($targetName);
        $source = DB::connection('tkj_source');

        if ($target->getDriverName() === 'sqlite' && realpath((string) $target->getDatabaseName()) === realpath($path)) {
            $this->error('Sumber dan tujuan adalah berkas yang sama. Atur DB_CONNECTION ke database tujuan di .env.');

            return self::FAILURE;
        }

        $sourceTables = collect(Schema::connection('tkj_source')->getTables())->map(fn ($t) => is_array($t) ? $t['name'] : $t->name)
            ->reject(fn ($t) => in_array($t, self::SKIP, true) || str_starts_with($t, 'sqlite_'));
        $targetTables = collect(Schema::connection($targetName)->getTables())->map(fn ($t) => is_array($t) ? $t['name'] : $t->name);

        $missing = $sourceTables->diff($targetTables);
        if ($missing->isNotEmpty()) {
            $this->error('Tabel belum ada di tujuan: '.$missing->implode(', ').'. Jalankan `php artisan migrate --force` dulu.');

            return self::FAILURE;
        }

        // Tolak bila tujuan sudah berisi data (tidak pernah menimpa).
        $occupied = $sourceTables->filter(fn ($t) => $target->table($t)->exists());
        if ($occupied->isNotEmpty()) {
            $this->error('Database tujuan sudah berisi data pada tabel: '.$occupied->implode(', ').'. Penyalinan dibatalkan; tidak ada yang diubah.');

            return self::FAILURE;
        }

        $counts = $sourceTables->mapWithKeys(fn ($t) => [$t => $source->table($t)->count()]);

        $this->table(['Tabel', 'Baris'], $counts->map(fn ($n, $t) => [$t, $n])->values()->all());

        if ($this->option('dry-run')) {
            $this->info('Dry run: tidak ada data yang disalin.');

            return self::SUCCESS;
        }

        Schema::connection($targetName)->disableForeignKeyConstraints();

        try {
            $target->transaction(function () use ($sourceTables, $source, $target): void {
                foreach ($sourceTables as $table) {
                    $source->table($table)->orderByRaw('1')->chunk(self::CHUNK, function ($rows) use ($target, $table): void {
                        $target->table($table)->insert($rows->map(fn ($r) => (array) $r)->all());
                    });
                }
            });
        } finally {
            Schema::connection($targetName)->enableForeignKeyConstraints();
        }

        // Verifikasi jumlah baris sama persis.
        $bad = $counts->filter(fn ($n, $t) => $target->table($t)->count() !== $n);
        if ($bad->isNotEmpty()) {
            $this->error('Jumlah baris tidak cocok pada: '.$bad->keys()->implode(', '));

            return self::FAILURE;
        }

        $this->info('Selesai. '.$counts->sum().' baris disalin dan jumlahnya cocok. Berkas SQLite tidak diubah.');

        return self::SUCCESS;
    }
}
