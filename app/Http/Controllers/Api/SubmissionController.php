<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Models\NewsPost;
use App\Services\ContentImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Berita dan foto galeri kiriman pengguna. Selalu berstatus "pending" sampai
 * admin menyetujuinya; pengguna hanya melihat dan menghapus kirimannya sendiri.
 */
class SubmissionController extends Controller
{
    public function __construct(private ContentImageService $images) {}

    public function index(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        return response()->json([
            'news' => NewsPost::where('submitted_by', $id)->latest('id')->limit(100)->get()->map(fn ($n) => [
                'id' => $n->id, 'title' => $n->title, 'category' => $n->category, 'summary' => $n->summary,
                'image' => $n->imageUrl(), 'review_status' => $n->review_status,
                'status_label' => $this->label($n->review_status), 'review_note' => $n->review_note,
            ])->values(),
            'gallery' => GalleryItem::where('submitted_by', $id)->latest('id')->limit(100)->get()->map(fn ($g) => [
                'id' => $g->id, 'caption' => $g->caption, 'image' => $g->imageUrl(),
                'review_status' => $g->review_status, 'status_label' => $this->label($g->review_status),
                'review_note' => $g->review_note,
            ])->values(),
        ]);
    }

    public function storeNews(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'category' => ['required', 'in:'.implode(',', NewsPost::CATEGORIES)],
            'summary' => ['required', 'string', 'min:10', 'max:300'],
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ContentImageService::rules(false),
        ]);

        $post = new NewsPost(collect($data)->only(['title', 'category', 'summary', 'body'])->all());
        $post->status = 'draft';
        $post->published_at = now();
        $post->review_status = 'pending';
        $post->submitted_by = $post->created_by = $request->user()->id;
        $this->save($post, $request, 'news');

        return response()->json(['message' => 'Berita terkirim. Berita akan tampil setelah disetujui admin.'], 201);
    }

    public function storeGallery(Request $request): JsonResponse
    {
        $data = $request->validate([
            'caption' => ['required', 'string', 'min:3', 'max:150'],
            'image' => ContentImageService::rules(true),
        ]);

        $item = new GalleryItem(['caption' => $data['caption']]);
        $item->is_published = false;
        $item->review_status = 'pending';
        $item->submitted_by = $item->created_by = $request->user()->id;
        $this->save($item, $request, 'gallery');

        return response()->json(['message' => 'Foto terkirim. Foto akan tampil setelah disetujui admin.'], 201);
    }

    public function destroyNews(Request $request, int $id): JsonResponse
    {
        $post = NewsPost::where('submitted_by', $request->user()->id)->findOrFail($id);
        $path = $post->image_path;
        $post->delete();
        $this->images->delete($path);

        return response()->json(['message' => 'Kiriman dihapus.']);
    }

    public function destroyGallery(Request $request, int $id): JsonResponse
    {
        $item = GalleryItem::where('submitted_by', $request->user()->id)->findOrFail($id);
        $path = $item->image_path;
        $item->delete();
        $this->images->delete($path);

        return response()->json(['message' => 'Kiriman dihapus.']);
    }

    private function save($model, Request $request, string $dir): void
    {
        $new = null;
        if ($request->hasFile('image')) {
            $new = $this->images->store($request->file('image'), $dir);
            $model->image_path = $new;
        }
        try {
            $model->save();
        } catch (Throwable $e) {
            $this->images->delete($new);
            throw $e;
        }
    }

    private function label(string $status): string
    {
        return ['pending' => 'Menunggu tinjauan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$status] ?? $status;
    }
}
