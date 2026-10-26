<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesWithImage;
use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/** Galeri kegiatan. Akses dibatasi middleware permission:manage_site_content. */
class GalleryController extends Controller
{
    use SavesWithImage;

    public function index(): View
    {
        return view('admin.gallery.index', [
            'items' => GalleryItem::query()->orderBy('sort_order')->orderByDesc('id')->paginate(24),
        ]);
    }

    public function create(): View
    {
        return view('admin.content.create', $this->page(
            'Tambah foto galeri',
            new GalleryItem(['is_published' => true, 'sort_order' => 0]),
            route('admin.gallery.store'),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $item = new GalleryItem($this->validated($request, true));
        $item->is_published = $request->boolean('is_published');
        $item->created_by = $request->user()->id;

        $this->persist($item, $request, 'gallery', allowRemove: false);

        return redirect()->route('admin.gallery.index')->with('success', 'Foto galeri disimpan.');
    }

    public function edit(GalleryItem $gallery): View
    {
        return view('admin.content.edit', $this->page(
            'Ubah foto galeri',
            $gallery,
            route('admin.gallery.update', $gallery),
            route('admin.gallery.destroy', $gallery),
        ));
    }

    public function update(Request $request, GalleryItem $gallery): RedirectResponse
    {
        $gallery->fill($this->validated($request, false));
        $gallery->is_published = $request->boolean('is_published');

        $this->persist($gallery, $request, 'gallery', allowRemove: false);

        return redirect()->route('admin.gallery.index')->with('success', 'Foto galeri diperbarui.');
    }

    public function destroy(GalleryItem $gallery): RedirectResponse
    {
        $this->destroyWithImage($gallery);

        return redirect()->route('admin.gallery.index')->with('success', 'Foto galeri dihapus.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        return Arr::except($request->validate([
            'caption' => ['required', 'string', 'min:3', 'max:150'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
            'image' => ContentImageService::rules($imageRequired),
        ]), ['image', 'is_published']);
    }

    private function page(string $heading, GalleryItem $item, string $action, ?string $delete = null): array
    {
        return [
            'heading' => $heading,
            'item' => $item,
            'action' => $action,
            'delete' => $delete,
            'form' => 'admin.gallery._form',
            'back' => route('admin.gallery.index'),
        ];
    }
}
