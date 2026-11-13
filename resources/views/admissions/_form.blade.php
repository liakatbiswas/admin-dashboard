@php
    $record = $application ?? null;
    $details = $record?->details ?? [];
    $guardian = $record?->guardian ?? [];
@endphp
@if ($errors->any()) <div role="alert" class="mb-4 text-bad">{{ $errors->first() }}</div> @endif
<div class="grid gap-4 sm:grid-cols-2">
    <x-field label="Academic year" name="academic_year_id" type="select" :value="$record?->academic_year_id" :options="($record ? $years->where('id', $record->academic_year_id) : $years)->pluck('year', 'id')->all()" required />
    <x-field label="Class" name="grade" type="select" :value="$record?->grade" :options="array_combine(\App\Models\Classroom::GRADES, \App\Models\Classroom::GRADES)" required />
    <x-field label="Name in Bangla" name="name_bn" :value="$record?->name_bn" required />
    <x-field label="Name in English" name="name_en" :value="$record?->name_en" required />
    <x-field label="Gender" name="gender" type="select" :value="$details['gender'] ?? 'Female'" :options="['Female' => __('Female'), 'Male' => __('Male'), 'Other' => __('Other')]" required />
    <x-field label="Date of birth" name="birth_date" type="date" :value="$details['birth_date'] ?? null" required />
    <x-field label="Religion" name="religion" :value="$details['religion'] ?? null" required />
    <x-field label="Birth registration number (optional)" name="birth_registration" :value="$details['birth_registration'] ?? null" maxlength="17" />
    <x-field label="Present address" name="present_address" type="textarea" :value="$details['present_address'] ?? null" required />
    <x-field label="Permanent address (optional)" name="permanent_address" type="textarea" :value="$details['permanent_address'] ?? null" />
    <label class="flex items-center gap-2"><input name="same_address" type="checkbox" value="1" @checked(old('same_address'))>{{ __('Same as present address') }}</label>
    <x-field label="Photo (optional)" name="photo" type="file" accept="image/jpeg,image/png,image/webp" />
    <x-field label="Previous school (optional)" name="previous_school" :value="$details['previous_school'] ?? null" />
    <x-field label="Last class (optional)" name="last_grade" type="number" min="1" max="10" :value="$details['last_grade'] ?? null" />
    <x-field label="Preferred shift" name="preferred_shift" type="select" :value="$record?->preferred_shift" :options="collect(\App\Models\Classroom::SHIFTS)->mapWithKeys(fn ($v) => [$v => __($v)])->all()" required />
    <x-field label="Preferred group (Classes 9–10 only)" name="preferred_group" type="select" :value="$record?->preferred_group" :options="['' => __('Not applicable')] + collect(\App\Models\Classroom::GROUPS)->mapWithKeys(fn ($v) => [$v => __($v)])->all()" />
</div>
<h2 class="mt-6 font-medium text-head">{{ __('Primary guardian') }}</h2>
<div class="mt-4 grid gap-4 sm:grid-cols-2">
    <x-field label="Guardian name" name="guardian_name" :value="$guardian['name'] ?? null" required />
    <x-field label="Relationship" name="guardian_relationship" :value="$guardian['relationship'] ?? null" required />
    <x-field label="Guardian mobile" name="guardian_phone" :value="$guardian['phone'] ?? null" required />
    <x-field label="Guardian email (optional)" name="guardian_email" type="email" :value="$guardian['email'] ?? null" />
    <x-field label="Guardian occupation (optional)" name="guardian_occupation" :value="$guardian['occupation'] ?? null" />
    <x-field label="Guardian address (optional)" name="guardian_address" :value="$guardian['address'] ?? null" />
</div>
