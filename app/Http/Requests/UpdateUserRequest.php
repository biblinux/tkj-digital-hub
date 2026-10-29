<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        return $actor
            && $target instanceof User
            && $actor->can('update', $target);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => strtolower(trim((string) $this->input('username'))),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($target->id),
            ],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($target->id),
            ],
            'class_name' => ['nullable', 'string', 'max:30'],
            'show_in_directory' => ['sometimes', 'boolean'],
        ];

        // Peran akun super admin tidak dapat diubah dari antarmuka.
        if (! $target->isSuperAdmin()) {
            $rules['role'] = [
                'required',
                Rule::in($this->user()->can('update', $target)
                    ? app(\App\Policies\UserPolicy::class)->assignableRoles($this->user())
                    : []),
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'role' => 'peran',
        ];
    }
}
