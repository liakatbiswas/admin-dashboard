<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $target = $this->route('user');
        if ($target && $target->is($this->user())) {
            return false;
        }

        return $this->user()?->hasRole('super-admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {

        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($target?->id)],
            'phone' => ['nullable', 'string', 'regex:/^(?:\+?88)?01[3-9][0-9]{8}$/'],
            'roles' => ['required', 'array', 'list', 'min:1'],
            'roles.*' => ['required', 'string', 'distinct', Rule::in($this->user()->assignableRoles())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('This email has been used before (can also be a deleted user,  see the Deleted filter).'),
        ];
    }
}
