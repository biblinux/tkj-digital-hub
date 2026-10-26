<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\GalleryItem;
use App\Models\NewsPost;
use App\Models\OrgMember;
use Illuminate\Http\JsonResponse;

/**
 * Konten publik untuk frontend statis. Hanya data yang berstatus terbit;
 * tidak ada data pengguna atau kolom internal (created_by, status, dll.).
 */
class ContentController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $news = NewsPost::query()->visible()
            ->orderByDesc('published_at')->orderByDesc('id')
            ->limit(100)->get()
            ->map(fn (NewsPost $n) => [
                'id' => $n->id,
                'date' => $n->published_at?->toDateString(),
                'category' => $n->category,
                'title' => $n->title,
                'summary' => $n->summary,
                'body' => $n->body,
                'image' => $n->imageUrl(),
            ])->values();

        $gallery = GalleryItem::query()->visible()
            ->orderBy('sort_order')->orderByDesc('id')
            ->limit(100)->get()
            ->map(fn (GalleryItem $g) => [
                'id' => $g->id,
                'caption' => $g->caption,
                'src' => $g->imageUrl(),
            ])->values();

        $facilities = Facility::query()->visible()
            ->orderBy('sort_order')->orderBy('id')
            ->limit(100)->get()
            ->map(fn (Facility $f) => [
                'id' => $f->id,
                'title' => $f->name,
                'text' => $f->description,
                'image' => $f->imageUrl(),
            ])->values();

        $members = OrgMember::query()->visible()
            ->orderBy('sort_order')->orderBy('id')
            ->limit(200)->get()
            ->groupBy('group');

        $person = fn (OrgMember $m) => ['name' => $m->name, 'role' => $m->position];
        $people = fn (string $group) => ($members[$group] ?? collect())->map($person)->values();

        $head = ($members['head'] ?? collect())->first();

        return response()
            ->json([
                'news' => $news,
                'gallery' => $gallery,
                'facilities' => $facilities,
                'org' => [
                    'head' => $head ? $person($head) : null,
                    'teachers' => $people('teachers'),
                    'students' => $people('students'),
                ],
                'it_team' => ($members['it_team'] ?? collect())->map(fn (OrgMember $m) => [
                    'name' => $m->name,
                    'role' => $m->position,
                    'tasks' => $m->taskList(),
                ])->values(),
            ])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
