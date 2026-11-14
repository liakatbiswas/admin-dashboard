<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return ['enrollment_id' => Enrollment::factory(),
            'student_id' => fn (array $attributes) => Enrollment::findOrFail($attributes['enrollment_id'])->student_id,
            'classroom_id' => fn (array $attributes) => Enrollment::findOrFail($attributes['enrollment_id'])->classroom_id,
            'date' => fn (array $attributes) => Enrollment::findOrFail($attributes['enrollment_id'])->classroom->academicYear->year.'-06-01',
            'roll' => fn (array $attributes) => Enrollment::findOrFail($attributes['enrollment_id'])->roll,
            'group' => fn (array $attributes) => Enrollment::findOrFail($attributes['enrollment_id'])->group,
            'status' => 'Present', 'version' => 1, 'recorded_by' => User::factory()];
    }
}
