<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\GalleryItem;
use App\Models\NewsPost;
use App\Models\OrgMember;
use App\Models\StudentProject;
use App\Models\User;
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

        $projects = StudentProject::query()->visible()->with('user')
            ->latest('id')->limit(100)->get()
            ->map(fn (StudentProject $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'text' => $p->description,
                'tech' => $p->techList(),
                'result' => $p->result,
                'link' => $p->link_url,
                'image' => $p->imageUrl(),
                'author' => $p->user?->shownName(),
                'class_name' => $p->user?->class_name,
            ])->values();

        // Direktori siswa: hanya nama, kelas, bio singkat, dan foto. Tanpa
        // email, username, atau data akun lain.
        $students = User::query()->inDirectory()
            ->orderByRaw('class_name is null')->orderBy('class_name')->orderBy('name')
            ->limit(500)->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->shownName(),
                'class_name' => $u->class_name,
                'bio' => $u->bio,
                'photo' => $u->photoUrl(),
            ])->values();

        return response()
            ->json([
                'projects' => $projects,
                'students' => $students,
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
