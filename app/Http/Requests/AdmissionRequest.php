<?php

namespace App\Http\Requests;

use App\Models\AdmissionOnOff;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->routeIs('register.store') ? AdmissionOnOff::isOn()
            : (bool) $this->user()?->can($this->routeIs('students.store') ? 'students.import' : 'applications.manage');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('guardian_phone'))) {
            $this->merge(['guardian_phone' => User::normalizePhone($this->input('guardian_phone'))]);
        }
        if ($this->boolean('same_address')) {
            $this->merge(['permanent_address' => $this->input('present_address')]);
        }
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'grade' => ['required', 'integer', Rule::in(Classroom::GRADES)],
            'name_bn' => ['required', 'string', 'max:255'], 'name_en' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Female', 'Male', 'Other'])],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before:today'],
            'religion' => ['required', 'string', 'max:100'],
            'present_address' => ['required', 'string', 'max:1000'], 'permanent_address' => ['nullable', 'string', 'max:1000'],
            'same_address' => ['nullable', 'boolean'],
            'birth_registration' => ['nullable', 'string', 'regex:/^[0-9]{17}$/'],
            'previous_school' => ['nullable', 'string', 'max:255'], 'last_grade' => ['nullable', 'integer', 'between:1,10'],
            'preferred_shift' => ['required', Rule::in(Classroom::SHIFTS)],
            'preferred_group' => [$this->integer('grade') >= 9 ? 'required' : 'prohibited', 'nullable', Rule::in(Classroom::GROUPS)],
            'guardian_name' => ['required', 'string', 'max:255'], 'guardian_relationship' => ['required', 'string', 'max:100'],
            'guardian_phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'guardian_email' => ['nullable', 'email', 'max:255'], 'guardian_occupation' => ['nullable', 'string', 'max:255'],
            'guardian_address' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024', Rule::dimensions()->maxWidth(2000)->maxHeight(2000)],
        ];
    }

    public function recordData(): array
    {
        $data = $this->validated();

        return [
            ...collect($data)->only(['academic_year_id', 'grade', 'name_bn', 'name_en', 'preferred_shift', 'preferred_group'])->all(),
            'details' => collect($data)->only(['gender', 'birth_date', 'religion', 'present_address', 'permanent_address', 'birth_registration', 'previous_school', 'last_grade'])->all(),
            'guardian' => collect($data)->filter(fn ($value, $key) => str_starts_with($key, 'guardian_'))
                ->mapWithKeys(fn ($value, $key) => [substr($key, 9) => $value])->all(),
        ];
    }
}
