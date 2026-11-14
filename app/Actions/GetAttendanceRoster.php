<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Support\Collection;

class GetAttendanceRoster
{
    /** @return Collection<int, array{enrollment: Enrollment, placement: array, attendance: ?Attendance}> */
    public function handle(Classroom $classroom, string $date): Collection
    {
        return Enrollment::where('academic_year_id', $classroom->academic_year_id)
            ->where(function ($query) use ($classroom, $date): void {
                $query->where('classroom_id', $classroom->id)
                    ->orWhereHas('changes', fn ($changes) => $changes->where('before->classroom_id', $classroom->id))
                    ->orWhereHas('attendances', fn ($attendance) => $attendance->where('classroom_id', $classroom->id)->where('date', $date));
            })
            ->with(['student:id,student_number,name_en,name_bn', 'classroom.academicYear', 'changes',
                'attendances' => fn ($query) => $query->where('date', $date)])
            ->get()->map(function (Enrollment $enrollment) use ($date): array {
                return ['enrollment' => $enrollment, 'placement' => $enrollment->placementOn($date), 'attendance' => $enrollment->attendances->first()];
            })->filter(fn (array $row) => $row['placement']['classroom_id'] === $classroom->id || $row['attendance']?->classroom_id === $classroom->id)
            ->sortBy(fn (array $row) => $row['attendance']?->roll ?? $row['placement']['roll'])->values();
    }
}
