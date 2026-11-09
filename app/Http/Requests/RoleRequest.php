<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // route-এর can:roles.manage দেখে
    }

    protected function prepareForValidation(): void
    {
        $role = $this->route('role');

        // protected role-এর নাম বদলানো যাবে না
        if ($role && in_array($role->name, config('permissions.protected_roles'), true)) {
            $this->merge(['name' => $role->name]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in($this->user()->assignablePermissions()->pluck('name')->all())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'নামে শুধু ছোট হাতের ইংরেজি অক্ষর, সংখ্যা, - ও _ চলবে (যেমন: content-editor)।',
            'name.unique' => 'এই নামে role আগেই আছে।',
            'permissions.*.in' => 'আপনার কাছে নেই এমন permission দেওয়া যাবে না।',
        ];
    }
}
