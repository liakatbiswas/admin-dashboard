<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentRollController extends Controller
{
    public function update(Request $request, Student $student, Enrollment $enrollment): RedirectResponse
    {
        $data = $request->validate([
            'roll' => ['required', 'integer', 'between:1,9999'],
            'expected_classroom_id' => ['required', 'integer'],
            'expected_roll' => ['required', 'integer', 'between:1,9999'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $student, $enrollment, $data): void {
            $classroom = Classroom::with('academicYear')->whereKey($enrollment->classroom_id)->lockForUpdate()->firstOrFail();
            $current = $student->enrollments()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($current->classroom_id !== (int) $data['expected_classroom_id'] || $current->classroom_id !== $classroom->id || $current->roll !== (int) $data['expected_roll']) {
                throw ValidationException::withMessages(['expected_roll' => __('The enrollment changed. Reload the student record and try again.')]);
            }
            if ($current->roll === (int) $data['roll']) {
                throw ValidationException::withMessages(['roll' => __('The new roll must differ from the current roll.')]);
            }
            if ($classroom->enrollments()->where('roll', $data['roll'])->whereKeyNot($current->id)->exists()) {
                throw ValidationException::withMessages(['roll' => __('This roll is already assigned in the classroom.')]);
            }
            $oldRoll = $current->roll;
            $current->update(['roll' => $data['roll']]);
            activity('enrollments')->causedBy($request->user())->performedOn($student)->withProperties([
                'enrollment_id' => $current->id, 'classroom_id' => $classroom->id,
                'academic_year' => $classroom->academicYear->year, 'grade' => $classroom->grade,
                'shift' => $classroom->shift, 'section' => $classroom->section,
                'old_roll' => $oldRoll, 'new_roll' => $current->roll, 'reason' => $data['reason'],
            ])->log('Class roll changed');
        }, 3);

        return redirect()->route('students.show', $student)->with('success', __('Class roll updated. Student ID is unchanged.'));
    }
}
