<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAttendance
{
    /** @param array<int, array{enrollment_id: int|string, status: ?string}> $entries */
    public function handle(User $actor, Classroom $classroom, string $date, array $entries): void
    {
        abort_unless($actor->can('attendance.record'), 403);
        $entries = collect($entries)->filter(fn (array $entry) => filled($entry['status'] ?? null))->values();
        if ($entries->isEmpty()) {
            throw ValidationException::withMessages(['entries' => __('Select at least one attendance status. Unmarked students are not saved.')]);
        }

        DB::transaction(function () use ($actor, $classroom, $date, $entries): void {
            $classroom = Classroom::with('academicYear')->whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            if (! $classroom->is_active || substr($date, 0, 4) !== (string) $classroom->academicYear->year) {
                throw ValidationException::withMessages(['classroom_id' => __('Choose an active classroom and a date in its academic year.')]);
            }
            $enrollments = Enrollment::with(['classroom.academicYear', 'changes' => fn ($query) => $query->lockForUpdate()])
                ->whereIn('id', $entries->pluck('enrollment_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($entries as $entry) {
                $enrollment = $enrollments->get($entry['enrollment_id']);
                if (! $enrollment || $enrollment->academic_year_id !== $classroom->academic_year_id) {
                    throw ValidationException::withMessages(['entries' => __('A selected student does not belong to this classroom on the attendance date. Reload the roster.')]);
                }
                $placement = $enrollment->placementOn($date);
                if ($placement['classroom_id'] !== $classroom->id) {
                    throw ValidationException::withMessages(['entries' => __('A selected student does not belong to this classroom on the attendance date. Reload the roster.')]);
                }
                if (Attendance::where('student_id', $enrollment->student_id)->where('date', $date)->lockForUpdate()->first()) {
                    throw ValidationException::withMessages(['entries' => __('Attendance is already recorded for a selected student on this date. Use correction permission to change it.')]);
                }
                $attendance = Attendance::create([
                    'student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id, 'classroom_id' => $classroom->id,
                    'date' => $date, 'roll' => $placement['roll'], 'group' => $placement['group'], 'status' => $entry['status'],
                    'version' => 1, 'recorded_by' => $actor->id,
                ]);
                activity('attendance')->causedBy($actor)->performedOn($attendance)
                    ->withProperties($attendance->only(['student_id', 'enrollment_id', 'classroom_id', 'date', 'roll', 'group', 'status']))->log('Attendance recorded');
            }
        }, 3);
    }
}
