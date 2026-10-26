<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesWithImage;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/** Fasilitas laboratorium. Akses dibatasi middleware permission:manage_site_content. */
class FacilityController extends Controller
{
    use SavesWithImage;

    public function index(): View
    {
        return view('admin.facilities.index', [
            'items' => Facility::query()->orderBy('sort_order')->orderBy('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.content.create', $this->page(
            'Tambah fasilitas',
            new Facility(['is_published' => true, 'sort_order' => 0]),
            route('admin.facilities.store'),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $facility = new Facility($this->validated($request));
        $facility->is_published = $request->boolean('is_published');
        $facility->created_by = $request->user()->id;

        $this->persist($facility, $request, 'facilities');

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas disimpan.');
    }

    public function edit(Facility $facility): View
    {
        return view('admin.content.edit', $this->page(
            'Ubah fasilitas',
            $facility,
            route('admin.facilities.update', $facility),
            route('admin.facilities.destroy', $facility),
        ));
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $facility->fill($this->validated($request));
        $facility->is_published = $request->boolean('is_published');

        $this->persist($facility, $request, 'facilities');

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas diperbarui.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $this->destroyWithImage($facility);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas dihapus.');
    }

    private function validated(Request $request): array
    {
        return Arr::except($request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
            'image' => ContentImageService::rules(false),
            'remove_image' => ['nullable', 'boolean'],
        ]), ['image', 'remove_image', 'is_published']);
    }

    private function page(string $heading, Facility $item, string $action, ?string $delete = null): array
    {
        return [
            'heading' => $heading,
            'item' => $item,
            'action' => $action,
            'delete' => $delete,
            'form' => 'admin.facilities._form',
            'back' => route('admin.facilities.index'),
        ];
    }
}
