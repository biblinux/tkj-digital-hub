<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Models\NewsPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Persetujuan berita dan foto galeri kiriman pengguna (permission:manage_site_content). */
class SubmissionModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.submissions.index', [
            'news' => NewsPost::with('submitter')->whereNotNull('submitted_by')
                ->orderByRaw("case review_status when 'pending' then 0 else 1 end")->latest('id')->limit(50)->get(),
            'gallery' => GalleryItem::with('submitter')->whereNotNull('submitted_by')
                ->orderByRaw("case review_status when 'pending' then 0 else 1 end")->latest('id')->limit(50)->get(),
        ]);
    }

    public function review(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
            'review_note' => ['nullable', 'string', 'max:300'],
        ]);

        $model = $type === 'news' ? NewsPost::findOrFail($id) : GalleryItem::findOrFail($id);
        abort_if($model->submitted_by === null, 404);

        $model->review_status = $data['decision'];
        $model->review_note = $data['decision'] === 'rejected' ? ($data['review_note'] ?? null) : null;

        $approved = $data['decision'] === 'approved';
        if ($type === 'news') {
            $model->status = $approved ? 'published' : 'draft';
            if ($approved) {
                $model->published_at = now();
            }
        } else {
            $model->is_published = $approved;
        }
        $model->save();

        return back()->with('success', 'Status kiriman diperbarui.');
    }
}
