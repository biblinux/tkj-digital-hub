<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor || ! $actor->can('create', User::class)) {
            return false;
        }

        // Hanya super admin yang boleh membuat akun admin.
        if ($this->input('role') === User::ROLE_ADMIN && ! $actor->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => strtolower(trim((string) $this->input('username'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'class_name' => trim((string) $this->input('class_name')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email'),
            ],
            'class_name' => ['nullable', 'string', 'max:30'],
            'role' => [
                'required',
                Rule::in([User::ROLE_STUDENT, User::ROLE_TECHNICIAN, User::ROLE_ADMIN]),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'username' => 'username',
            'email' => 'email',
            'role' => 'peran',
            'class_name' => 'kelas',
        ];
    }
}
