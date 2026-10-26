<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/** URL publik untuk foto yang disimpan di disk "public". */
trait HasPublicImage
{
    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }
}
