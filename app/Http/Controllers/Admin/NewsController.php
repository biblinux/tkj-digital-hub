<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesWithImage;
use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Berita dan pengumuman. Akses dibatasi middleware permission:manage_site_content. */
class NewsController extends Controller
{
    use SavesWithImage;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(NewsPost::STATUSES))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $items = NewsPost::query()->where('review_status', 'approved')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
                $q->where('title', 'like', $like);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.news.index', ['items' => $items, 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('admin.content.create', $this->page(
            'Tambah berita',
            new NewsPost(['status' => 'draft', 'published_at' => now()]),
            route('admin.news.store'),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $post = new NewsPost($this->validated($request));
        $this->applyPublishDate($post);
        $post->created_by = $post->updated_by = $request->user()->id;

        $this->persist($post, $request, 'news');

        return redirect()->route('admin.news.index')->with('success', 'Berita disimpan.');
    }

    public function edit(NewsPost $news): View
    {
        return view('admin.content.edit', $this->page(
            'Ubah berita',
            $news,
            route('admin.news.update', $news),
            route('admin.news.destroy', $news),
        ));
    }

    public function update(Request $request, NewsPost $news): RedirectResponse
    {
        $news->fill($this->validated($request));
        $this->applyPublishDate($news);
        $news->updated_by = $request->user()->id;

        $this->persist($news, $request, 'news');

        return redirect()->route('admin.news.index')->with('success', 'Berita diperbarui.');
    }

    public function destroy(NewsPost $news): RedirectResponse
    {
        $this->destroyWithImage($news);

        return redirect()->route('admin.news.index')->with('success', 'Berita dihapus.');
    }

    private function validated(Request $request): array
    {
        return Arr::except($request->validate([
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'category' => ['required', Rule::in(NewsPost::CATEGORIES)],
            'summary' => ['required', 'string', 'min:10', 'max:300'],
            'body' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(array_keys(NewsPost::STATUSES))],
            'published_at' => ['nullable', 'date', 'after:1999-12-31'],
            'image' => ContentImageService::rules(false),
            'remove_image' => ['nullable', 'boolean'],
        ]), ['image', 'remove_image']);
    }

    /** Berita terbit tanpa tanggal otomatis bertanggal hari ini. */
    private function applyPublishDate(NewsPost $post): void
    {
        if ($post->status === 'published' && ! $post->published_at) {
            $post->published_at = now();
        }
    }

    private function page(string $heading, NewsPost $item, string $action, ?string $delete = null): array
    {
        return [
            'heading' => $heading,
            'item' => $item,
            'action' => $action,
            'delete' => $delete,
            'form' => 'admin.news._form',
            'back' => route('admin.news.index'),
        ];
    }
}
