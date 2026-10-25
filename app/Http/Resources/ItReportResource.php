<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representasi laporan untuk pelapor. Tidak memuat catatan internal
 * maupun data pribadi pengguna lain.
 *
 * @mixin \App\Models\ItReport
 */
class ItReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'category' => $this->category,
            'category_label' => $this->categoryLabel(),
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'urgency' => $this->urgency,
            'urgency_label' => $this->urgencyLabel(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'is_final' => $this->isFinal(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'updates' => ItReportUpdateResource::collection($this->whenLoaded('updates')),
        ];
    }
}
