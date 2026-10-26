<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Services\ContentImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

trait SavesWithImage
{
    /**
     * Menyimpan model beserta foto barunya. Foto lama dihapus hanya setelah
     * data berhasil tersimpan; jika penyimpanan gagal, foto baru dibersihkan.
     */
    protected function persist(Model $model, Request $request, string $dir, bool $allowRemove = true): void
    {
        $images = app(ContentImageService::class);
        $old = $model->image_path;
        $new = null;

        if ($request->hasFile('image')) {
            $new = $images->store($request->file('image'), $dir);
            $model->image_path = $new;
        } elseif ($allowRemove && $request->boolean('remove_image')) {
            $model->image_path = null;
        }

        try {
            $model->save();
        } catch (Throwable $e) {
            $images->delete($new);
            throw $e;
        }

        if ($old && $old !== $model->image_path) {
            $images->delete($old);
        }
    }

    protected function destroyWithImage(Model $model): void
    {
        $path = $model->image_path;
        $model->delete();
        app(ContentImageService::class)->delete($path);
    }
}
