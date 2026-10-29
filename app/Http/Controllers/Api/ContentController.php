<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\GalleryItem;
use App\Models\NewsPost;
use App\Models\OrgMember;
use App\Models\SiteSetting;
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

        $projects = StudentProject::query()->visible()->with('user:id,name,display_name')
            ->orderByDesc('reviewed_at')->orderByDesc('id')
            ->limit(100)->get()
            ->map(fn (StudentProject $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'text' => $p->description,
                'tech' => $p->tech ?? [],
                'result' => $p->result,
                'link' => $p->link_url,
                'image' => $p->imageUrl(),
                'author' => $p->user?->shownName(),
            ])->values();

        // Direktori otomatis: hanya akun aktif yang tidak disembunyikan. Tanpa username, email, atau data internal.
        $directory = fn (string $role) => User::query()->inDirectory($role)
            ->orderByRaw('lower(coalesce(display_name, name))')->orderBy('id')
            ->limit(500)->get()
            ->map(fn (User $u) => [
                'name' => $u->shownName(),
                'class_name' => $u->class_name,
                'bio' => $u->bio,
                'photo' => $u->photoUrl(),
            ])->values();

        $s = SiteSetting::allValues();
        $school = config('tkj.school');
        $competencies = collect(SiteSetting::lines($s['competencies'] ?? ''))->map(function (string $line) {
            [$title, $text] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

            return ['title' => $title, 'text' => $text];
        })->filter(fn ($c) => $c['title'] !== '')->values();

        $site = [
            'vision' => ($s['vision'] ?? null) ?: null,
            'mission' => SiteSetting::lines($s['mission'] ?? ''),
            'about' => ($s['about'] ?? null) ?: null,
            'competencies' => $competencies,
            'contact' => [
                'address' => (($s['address'] ?? null) ?: ($school['address'] ?? null)) ?: null,
                'phone' => (($s['phone'] ?? null) ?: ($school['phone'] ?? null)) ?: null,
                // Kunci sengaja bernama "mail": respons publik tidak boleh memuat kata "email" (uji kebocoran data pengguna).
                'mail' => (($s['email'] ?? null) ?: ($school['email'] ?? null)) ?: null,
                'map_url' => (($s['map_url'] ?? null) ?: ($school['map_url'] ?? null)) ?: null,
            ],
        ];

        return response()
            ->json([
                'site' => $site,
                'students' => $directory(User::ROLE_STUDENT),
                'technicians' => $directory(User::ROLE_TECHNICIAN),
                'news' => $news,
                'gallery' => $gallery,
                'projects' => $projects,
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
