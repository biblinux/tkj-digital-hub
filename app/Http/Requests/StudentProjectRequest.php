<?php

namespace App\Http\Requests;

use App\Services\ContentImageService;
use Illuminate\Foundation\Http\FormRequest;

class StudentProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Teknologi boleh dikirim sebagai teks dipisah koma atau sebagai larik. */
    protected function prepareForValidation(): void
    {
        $tech = $this->input('tech');
        if (is_string($tech)) {
            $tech = explode(',', $tech);
        }
        if (is_array($tech)) {
            $tech = array_values(array_unique(array_filter(array_map(
                fn ($t) => is_string($t) ? trim($t) : '',
                $tech
            ), fn ($t) => $t !== '')));
        }

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => trim((string) $this->input('description')),
            'result' => trim((string) $this->input('result')) ?: null,
            'link_url' => trim((string) $this->input('link_url')) ?: null,
            'tech' => $tech ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:1500'],
            'tech' => ['nullable', 'array', 'max:8'],
            'tech.*' => ['string', 'max:30'],
            'result' => ['nullable', 'string', 'max:300'],
            'link_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'image' => ContentImageService::rules(false),
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Judul minimal 5 karakter.',
            'title.required' => 'Judul wajib diisi.',
            'description.min' => 'Deskripsi minimal 20 karakter.',
            'description.required' => 'Deskripsi wajib diisi.',
            'link_url.url' => 'Tautan harus diawali http:// atau https://.',
            'tech.max' => 'Teknologi maksimal 8 item.',
            'tech.*.max' => 'Setiap teknologi maksimal 30 karakter.',
        ];
    }
}
