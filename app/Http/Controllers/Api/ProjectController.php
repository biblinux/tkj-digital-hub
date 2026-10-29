<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentProjectRequest;
use App\Models\StudentProject;
use App\Services\ContentImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Proyek siswa milik pengguna yang login. Proyek baru atau yang diubah menunggu
 * persetujuan admin sebelum tampil di situs publik.
 */
class ProjectController extends Controller
{
    public function __construct(private ContentImageService $images) {}

    public function mine(Request $request): JsonResponse
    {
        $items = StudentProject::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')->limit(100)->get();

        return response()->json(['projects' => $items->map(fn ($p) => $this->payload($p))->values()]);
    }

    public function store(StudentProjectRequest $request): JsonResponse
    {
        $user = $request->user();

        if (StudentProject::where('user_id', $user->id)->count() >= StudentProject::MAX_PER_USER) {
            return response()->json([
                'message' => 'Batas '.StudentProject::MAX_PER_USER.' proyek per akun tercapai. Hapus proyek lama terlebih dahulu.',
            ], 422);
        }

        $data = $request->validated();
        $project = new StudentProject(collect($data)->only(['title', 'description', 'tech', 'result', 'link_url'])->all());
        $project->user_id = $user->id;
        $project->status = StudentProject::PENDING;

        if ($request->hasFile('image')) {
            $project->image_path = $this->images->store($request->file('image'), 'projects');
        }
        $project->save();

        return response()->json([
            'message' => 'Proyek dikirim dan menunggu persetujuan admin.',
            'project' => $this->payload($project),
        ], 201);
    }

    public function update(StudentProjectRequest $request, int $project): JsonResponse
    {
        $item = $this->owned($request, $project);
        $data = $request->validated();

        $item->fill(collect($data)->only(['title', 'description', 'tech', 'result', 'link_url'])->all());

        if ($request->hasFile('image')) {
            $old = $item->image_path;
            $item->image_path = $this->images->store($request->file('image'), 'projects');
            $this->images->delete($old);
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($item->image_path);
            $item->image_path = null;
        }

        // Perubahan harus ditinjau ulang sebelum tampil.
        $item->status = StudentProject::PENDING;
        $item->reject_reason = null;
        $item->reviewed_by = null;
        $item->reviewed_at = null;
        $item->save();

        return response()->json([
            'message' => 'Proyek diperbarui dan menunggu persetujuan admin.',
            'project' => $this->payload($item),
        ]);
    }

    public function destroy(Request $request, int $project): JsonResponse
    {
        $item = $this->owned($request, $project);
        $this->images->delete($item->image_path);
        $item->delete();

        return response()->json(['message' => 'Proyek dihapus.']);
    }

    /** Hanya pemilik; proyek orang lain dijawab 404 agar keberadaannya tidak bocor. */
    private function owned(Request $request, int $id): StudentProject
    {
        return StudentProject::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    private function payload(StudentProject $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'text' => $p->description,
            'tech' => $p->tech ?? [],
            'result' => $p->result,
            'link' => $p->link_url,
            'image' => $p->imageUrl(),
            'status' => $p->status,
            'status_label' => $p->statusLabel(),
            'reject_reason' => $p->status === StudentProject::REJECTED ? $p->reject_reason : null,
        ];
    }
}
