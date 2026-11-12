<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $role = $this->route('role');

        // The name of a protected role cannot be changed.
        if ($role && in_array($role->name, config('permissions.protected_roles'), true)) {
            $this->merge(['name' => $role->name]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {

        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in($this->user()->assignablePermissions()->pluck('name')->all())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name may only contain lowercase English letters, numbers, hyphens (-), and underscores (_) (e.g., content-editor).',
            'name.unique' => 'A role with this name already exists.',
            'permissions.*.in' => 'You cannot grant permissions that you do not possess yourself.',
        ];
    }
}
