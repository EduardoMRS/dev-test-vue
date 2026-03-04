<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name'   => ['sometimes', 'required', 'string', 'max:255'],
            'email'  => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'role'   => ['sometimes', 'required', 'string', 'in:' . implode(',', [
                User::ROLE_USER,
                User::ROLE_MODERATOR,
                User::ROLE_ADMIN,
            ])],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
