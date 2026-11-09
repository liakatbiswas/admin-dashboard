<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // route-এর can: middleware ই অনুমতি দেখে
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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
            'email.unique' => 'এই ইমেইল আগে ব্যবহৃত হয়েছে (মুছে ফেলা user-ও হতে পারে, Deleted ফিল্টারে দেখুন)।',
        ];
    }
}
