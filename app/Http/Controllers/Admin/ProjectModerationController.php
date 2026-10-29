<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProject;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Peninjauan proyek siswa. Akses dibatasi middleware permission:manage_site_content. */
class ProjectModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', StudentProject::PENDING);
        if (! array_key_exists($status, StudentProject::STATUSES) && $status !== 'all') {
            $status = StudentProject::PENDING;
        }

        return view('admin.projects.index', [
            'projects' => StudentProject::query()->with('user:id,name,display_name,username')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest('id')->paginate(20)->withQueryString(),
            'status' => $status,
            'counts' => StudentProject::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(StudentProject $project): View
    {
        return view('admin.projects.show', ['project' => $project->load('user:id,name,display_name,username', 'reviewer:id,name,display_name')]);
    }

    public function approve(Request $request, StudentProject $project): RedirectResponse
    {
        $project->status = StudentProject::PUBLISHED;
        $project->reject_reason = null;
        $project->reviewed_by = $request->user()->id;
        $project->reviewed_at = now();
        $project->save();

        return redirect()->route('admin.projects.index')->with('success', 'Proyek disetujui dan tampil di situs.');
    }

    public function reject(Request $request, StudentProject $project): RedirectResponse
    {
        $data = $request->validate(['reject_reason' => ['required', 'string', 'min:3', 'max:300']]);

        $project->status = StudentProject::REJECTED;
        $project->reject_reason = trim($data['reject_reason']);
        $project->reviewed_by = $request->user()->id;
        $project->reviewed_at = now();
        $project->save();

        return redirect()->route('admin.projects.index')->with('success', 'Proyek ditolak. Alasan akan terlihat oleh pemiliknya.');
    }

    public function destroy(StudentProject $project, ContentImageService $images): RedirectResponse
    {
        $images->delete($project->image_path);
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Proyek dihapus.');
    }
}
