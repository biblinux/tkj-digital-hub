<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ItReportUpdate */
class ItReportUpdateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'note' => $this->note,
            'author' => $this->whenLoaded('author', fn () => $this->author?->shownName()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
