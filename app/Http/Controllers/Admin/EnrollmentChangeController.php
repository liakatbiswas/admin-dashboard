<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ChangeEnrollment;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnrollmentChangeController extends Controller
{
    public function transfer(Request $request, Student $student, Enrollment $enrollment, ChangeEnrollment $change): RedirectResponse
    {
        $data = $this->validateChange($request) + $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'roll' => ['required', 'integer', 'between:1,9999'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $change->handle($request->user(), $student, $enrollment, 'transfer', $data);

        return redirect()->route('students.show', $student)->with('success', __('Shift or section updated. Student ID is unchanged.'));
    }

    public function group(Request $request, Student $student, Enrollment $enrollment, ChangeEnrollment $change): RedirectResponse
    {
        $data = $this->validateChange($request) + $request->validate(['group' => ['required', Rule::in(Classroom::GROUPS)]]);
        $change->handle($request->user(), $student, $enrollment, 'group', $data);

        return redirect()->route('students.show', $student)->with('success', __('Group updated. Previous records are preserved.'));
    }

    /** @return array{expected_classroom_id: int|string, expected_roll: int|string, expected_group: ?string, effective_date: string, reason: string} */
    private function validateChange(Request $request): array
    {
        return $request->validate([
            'expected_classroom_id' => ['required', 'integer'],
            'expected_roll' => ['required', 'integer', 'between:1,9999'],
            'expected_group' => ['present', 'nullable', Rule::in(Classroom::GROUPS)],
            'effective_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Dhaka')->toDateString()],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
    }
}
