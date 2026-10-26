<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrgMember extends Model
{
    public const GROUPS = [
        'head' => 'Kepala jurusan',
        'teachers' => 'Guru dan pembimbing',
        'students' => 'Pengurus organisasi siswa',
        'it_team' => 'Tim IT',
    ];

    protected $fillable = ['group', 'name', 'position', 'tasks', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Daftar tugas: satu tugas per baris, baris kosong diabaikan. */
    public function taskList(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/u', (string) $this->tasks) ?: []),
            fn (string $line) => $line !== ''
        ));
    }
}
