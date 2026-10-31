<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProject;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Peninjauan proyek siswa. Akses dibatasi middleware permission:manage_site_content. */
class ProjectModerationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(StudentProject::STATUSES))],
        ]);

        $items = StudentProject::query()->with('user')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("case status when 'pending' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.projects.index', [
            'items' => $items,
            'filters' => $filters,
            'pending' => StudentProject::where('status', 'pending')->count(),
        ]);
    }

    public function show(StudentProject $project): View
    {
        return view('admin.projects.show', ['project' => $project->load('user')]);
    }

    public function review(Request $request, StudentProject $project): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['published', 'rejected', 'pending'])],
            'review_note' => ['nullable', 'string', 'max:300'],
        ]);

        $project->status = $data['decision'];
        $project->review_note = $data['decision'] === 'rejected' ? ($data['review_note'] ?? null) : null;
        $project->save();

        return redirect()->route('admin.projects.index')
            ->with('success', 'Status proyek diperbarui: '.StudentProject::STATUSES[$project->status].'.');
    }

    public function destroy(StudentProject $project, ContentImageService $images): RedirectResponse
    {
        $path = $project->image_path;
        $project->delete();
        $images->delete($path);

        return redirect()->route('admin.projects.index')->with('success', 'Proyek dihapus.');
    }
}
