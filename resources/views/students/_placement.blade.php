@cannot('students.rolls.manage')<p class="mb-4 text-warn">{{ __('You need Class Roll permission to complete this enrollment.') }}</p>@endcannot
<fieldset @disabled(!auth()->user()->can('students.rolls.manage'))>
<div class="grid gap-4 sm:grid-cols-2">
    <x-field label="Final classroom" name="classroom_id" type="select" :options="$classrooms->mapWithKeys(fn ($room) => [$room->id => $room->academicYear->year.' / '.$room->grade.' / '.__($room->shift).' / '.$room->section])->all()" required />
    <x-field label="Class roll" name="roll" type="number" min="1" max="9999" required />
    <x-field label="Final group (Classes 9–10 only)" name="group" type="select" :options="['' => __('Not applicable')] + collect(\App\Models\Classroom::GROUPS)->mapWithKeys(fn ($v) => [$v => __($v)])->all()" />
    <x-field label="Verification reason" name="reason" required />
    @role('super-admin')<x-field label="Seat override reason (only when full)" name="override_reason" />@endrole
</div>
<label class="my-4 flex items-center gap-2"><input type="checkbox" name="documents_checked" value="1" required>{{ __('I checked the school records and identity documents.') }}</label>

</fieldset>
