<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentProject;
use App\Services\ContentImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Proyek milik akun yang sedang masuk. Setiap proyek hanya bisa dilihat,
 * diubah, dan dihapus oleh pemiliknya; tampil publik setelah disetujui admin.
 */
class ProjectController extends Controller
{
    public function __construct(private ContentImageService $images) {}

    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->projects()->latest('id')->limit(100)->get();

        return response()->json(['projects' => $items->map(fn ($p) => $this->payload($p))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, true);

        $project = new StudentProject($data);
        $project->user_id = $request->user()->id;
        $project->status = config('tkj.project_auto_publish') ? 'published' : 'pending';
        $this->save($project, $request);

        return response()->json([
            'message' => $project->status === 'published'
                ? 'Proyek berhasil dikirim dan sudah tampil.'
                : 'Proyek berhasil dikirim. Proyek akan tampil setelah ditinjau admin.',
            'project' => $this->payload($project),
        ], 201);
    }

    public function update(Request $request, int $project): JsonResponse
    {
        $model = $request->user()->projects()->findOrFail($project);
        $model->fill($this->validated($request, false));

        // Proyek yang diubah perlu ditinjau ulang kecuali terbit otomatis.
        $model->status = config('tkj.project_auto_publish') ? 'published' : 'pending';
        $model->review_note = null;

        if ($request->boolean('remove_image') && ! $request->hasFile('image')) {
            $this->images->delete($model->image_path);
            $model->image_path = null;
        }
        $this->save($model, $request);

        return response()->json(['message' => 'Proyek diperbarui.', 'project' => $this->payload($model)]);
    }

    public function destroy(Request $request, int $project): JsonResponse
    {
        $model = $request->user()->projects()->findOrFail($project);
        $path = $model->image_path;
        $model->delete();
        $this->images->delete($path);

        return response()->json(['message' => 'Proyek dihapus.']);
    }

    private function validated(Request $request, bool $creating): array
    {
        $request->merge(['link_url' => trim((string) $request->input('link_url')) ?: null]);

        return $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'technologies' => ['nullable', 'string', 'max:200'],
            'result' => ['nullable', 'string', 'max:300'],
            'link_url' => ['nullable', 'url:http,https', 'max:255'],
            'image' => ContentImageService::rules(false),
        ], [
            'title.min' => 'Judul proyek minimal 5 karakter.',
            'description.min' => 'Deskripsi minimal 20 karakter.',
            'link_url.url' => 'Tautan harus diawali http:// atau https://.',
        ]) + [];
    }

    private function save(StudentProject $project, Request $request): void
    {
        $old = $project->image_path;
        $new = null;
        if ($request->hasFile('image')) {
            $new = $this->images->store($request->file('image'), 'projects');
            $project->image_path = $new;
        }
        try {
            $project->save();
        } catch (Throwable $e) {
            $this->images->delete($new);
            throw $e;
        }
        if ($new && $old) {
            $this->images->delete($old);
        }
    }

    private function payload(StudentProject $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'description' => $p->description,
            'technologies' => $p->technologies,
            'result' => $p->result,
            'link_url' => $p->link_url,
            'image' => $p->imageUrl(),
            'status' => $p->status,
            'status_label' => StudentProject::STATUSES[$p->status] ?? $p->status,
            'review_note' => $p->review_note,
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }
}
