<?php

namespace App\Actions;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\EnrollmentChange;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeEnrollment
{
    /** @param array{expected_classroom_id: int|string, expected_roll: int|string, expected_group: ?string, effective_date: string, reason: string, classroom_id?: int|string, roll?: int|string, group?: string, override_reason?: ?string} $data */
    public function handle(User $actor, Student $student, Enrollment $enrollment, string $type, array $data): void
    {
        abort_unless(in_array($type, ['transfer', 'group'], true), 422);
        abort_unless($actor->can($type === 'transfer' ? 'students.transfers.manage' : 'students.groups.manage'), 403);
        if ($type === 'transfer') {
            abort_unless($actor->can('students.rolls.manage'), 403);
        }

        DB::transaction(function () use ($actor, $student, $enrollment, $type, $data): void {
            $destinationId = $type === 'transfer' ? (int) $data['classroom_id'] : $enrollment->classroom_id;
            $rooms = Classroom::with('academicYear')->whereIn('id', [$enrollment->classroom_id, $destinationId])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $current = $student->enrollments()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            $this->check($current->classroom_id === $enrollment->classroom_id
                && $current->classroom_id === (int) $data['expected_classroom_id']
                && $current->roll === (int) $data['expected_roll']
                && $current->group === $data['expected_group'], 'expected_classroom_id', 'The enrollment changed. Reload the student record and try again.');
            $source = $rooms->get($current->classroom_id);
            $destination = $rooms->get($destinationId);
            $this->check($destination !== null, 'classroom_id', 'Select a valid destination classroom.');
            $year = $source->academicYear->year;
            $this->check(substr($data['effective_date'], 0, 4) === (string) $year, 'effective_date', 'The effective date must be within the enrollment academic year.');
            $latestDate = $current->changes()->max('effective_date');
            $this->check($latestDate === null || $data['effective_date'] >= substr($latestDate, 0, 10), 'effective_date', 'The effective date cannot precede the last enrollment change.');
            $before = $this->snapshot($current, $source);
            $overrideReason = null;

            if ($type === 'transfer') {
                $this->check($source->id !== $destination->id, 'classroom_id', 'Select a different shift or section.');
                $this->check($destination->academic_year_id === $current->academic_year_id && $destination->grade === $source->grade,
                    'classroom_id', 'Transfers must stay in the same academic year and class.');
                $this->check($destination->is_active, 'classroom_id', 'This classroom is inactive.');
                $this->check(! $destination->enrollments()->where('roll', $data['roll'])->exists(), 'roll', 'This roll is already assigned in the classroom.');
                $full = $destination->enrollments()->count() >= $destination->capacity;
                $this->check(! $full || ($actor->hasRole('super-admin') && filled($data['override_reason'] ?? null)),
                    'classroom_id', 'This classroom is full. A Super Admin must supply an override reason.');
                $overrideReason = $full ? $data['override_reason'] : null;
                $current->update(['classroom_id' => $destination->id, 'roll' => $data['roll']]);
            } else {
                $this->check($source->grade >= 9 && $source->allowsGroup($data['group']), 'group', 'Group changes are only available for classes 9 and 10.');
                $this->check($current->group !== $data['group'], 'group', 'Select a different group.');
                $current->update(['group' => $data['group']]);
            }

            $change = EnrollmentChange::create([
                'enrollment_id' => $current->id, 'type' => $type, 'before' => $before,
                'after' => $this->snapshot($current, $destination), 'effective_date' => $data['effective_date'],
                'reason' => $data['reason'], 'override_reason' => $overrideReason, 'changed_by' => $actor->id,
            ]);
            activity('enrollments')->causedBy($actor)->performedOn($student)
                ->withProperties($change->only(['enrollment_id', 'type', 'before', 'after', 'effective_date', 'reason', 'override_reason']) + ['enrollment_change_id' => $change->id])
                ->log($type === 'transfer' ? 'Shift or section changed' : 'Student group changed');
        }, 3);
    }

    /** @return array{classroom_id: int, academic_year: int, grade: int, shift: string, section: string, roll: int, group: ?string} */
    private function snapshot(Enrollment $enrollment, Classroom $classroom): array
    {
        return ['classroom_id' => $classroom->id, 'academic_year' => $classroom->academicYear->year,
            'grade' => $classroom->grade, 'shift' => $classroom->shift, 'section' => $classroom->section,
            'roll' => $enrollment->roll, 'group' => $enrollment->group];
    }

    private function check(bool $condition, string $field, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => __($message)]);
        }
    }
}
