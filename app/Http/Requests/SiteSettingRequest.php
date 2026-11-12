<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.site.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'site_title' => ['required', 'string', 'max:150'],
            'site_subtitle' => ['nullable', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'url', 'max:255'],
            'favicon' => [
                'nullable', 'bail', 'image', 'mimes:png,jpg,jpeg,webp', 'max:512',
                Rule::dimensions()->maxWidth(512)->maxHeight(512),
            ],
            'light_logo' => [
                'nullable', 'bail', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048',
                Rule::dimensions()->maxWidth(3000)->maxHeight(1500),
            ],
            'dark_logo' => [
                'nullable', 'bail', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048',
                Rule::dimensions()->maxWidth(3000)->maxHeight(1500),
            ],
        ];
    }
}
