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

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {

        $target = $this->route('user');
        $isSelf = $target && $target->is($this->user());

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($target?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => [$isSelf ? 'nullable' : 'required',
                Rule::in($this->user()->assignableRoles())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email has been used before (can also be a deleted user,  see the Deleted filter).',
        ];
    }
}
