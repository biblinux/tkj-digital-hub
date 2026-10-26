<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrgMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Struktur organisasi dan tim IT. Hanya nama dan jabatan, tanpa foto atau data
 * pribadi lain. Akses dibatasi middleware permission:manage_site_content.
 */
class OrgMemberController extends Controller
{
    public function index(): View
    {
        return view('admin.organization.index', [
            'members' => OrgMember::query()->orderBy('group')->orderBy('sort_order')->orderBy('id')->get()
                ->groupBy('group'),
        ]);
    }

    public function create(Request $request): View
    {
        $group = $request->query('group');

        return view('admin.content.create', $this->page(
            'Tambah anggota',
            new OrgMember([
                'group' => array_key_exists((string) $group, OrgMember::GROUPS) ? $group : 'teachers',
                'is_published' => true,
                'sort_order' => 0,
            ]),
            route('admin.organization.store'),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $member = new OrgMember($this->validated($request));
        $member->is_published = $request->boolean('is_published');
        $member->created_by = $request->user()->id;
        $member->save();

        return redirect()->route('admin.organization.index')->with('success', 'Anggota disimpan.');
    }

    public function edit(OrgMember $member): View
    {
        return view('admin.content.edit', $this->page(
            'Ubah anggota',
            $member,
            route('admin.organization.update', $member),
            route('admin.organization.destroy', $member),
        ));
    }

    public function update(Request $request, OrgMember $member): RedirectResponse
    {
        $member->fill($this->validated($request));
        $member->is_published = $request->boolean('is_published');
        $member->save();

        return redirect()->route('admin.organization.index')->with('success', 'Anggota diperbarui.');
    }

    public function destroy(OrgMember $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('admin.organization.index')->with('success', 'Anggota dihapus.');
    }

    private function validated(Request $request): array
    {
        return Arr::except($request->validate([
            'group' => ['required', Rule::in(array_keys(OrgMember::GROUPS))],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'position' => ['required', 'string', 'min:2', 'max:100'],
            'tasks' => ['nullable', 'string', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
        ]), ['is_published']);
    }

    private function page(string $heading, OrgMember $item, string $action, ?string $delete = null): array
    {
        return [
            'heading' => $heading,
            'item' => $item,
            'action' => $action,
            'delete' => $delete,
            'form' => 'admin.organization._form',
            'back' => route('admin.organization.index'),
        ];
    }
}
