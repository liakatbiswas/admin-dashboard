<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentChangeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory()->state(['classroom_id' => Classroom::factory()->state(['grade' => 9]), 'group' => 'Science']), 'type' => 'group',
            'before' => function (array $attributes): array {
                $enrollment = Enrollment::with('classroom.academicYear')->findOrFail($attributes['enrollment_id']);

                return ['classroom_id' => $enrollment->classroom_id, 'academic_year' => $enrollment->classroom->academicYear->year,
                    'grade' => $enrollment->classroom->grade, 'shift' => $enrollment->classroom->shift,
                    'section' => $enrollment->classroom->section, 'roll' => $enrollment->roll, 'group' => 'Humanities'];
            },
            'after' => fn (array $attributes) => array_replace($attributes['before'], ['group' => 'Science']),
            'effective_date' => fn (array $attributes) => $attributes['before']['academic_year'].'-01-01',
            'reason' => 'School record correction', 'changed_by' => User::factory(),
        ];
    }
}
