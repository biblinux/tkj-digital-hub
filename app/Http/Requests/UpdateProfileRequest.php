<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'display_name' => trim((string) $this->input('display_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ];
        foreach (['bio', 'class_name'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = trim((string) $this->input($field));
            }
        }
        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'bio' => ['nullable', 'string', 'max:200'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'show_in_directory' => ['sometimes', 'boolean'],
            'current_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'email.email' => 'Format email tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'bio.max' => 'Bio maksimal 200 karakter.',
            'class_name.max' => 'Kelas maksimal 30 karakter.',
        ];
    }

    /** Mengganti email wajib disertai kata sandi saat ini. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->input('email') === strtolower((string) $this->user()->email)) {
                return;
            }
            if (! Hash::check((string) $this->input('current_password'), $this->user()->password)) {
                $validator->errors()->add('current_password', 'Untuk mengganti email, masukkan kata sandi Anda saat ini dengan benar.');
            }
        }];
    }
}
