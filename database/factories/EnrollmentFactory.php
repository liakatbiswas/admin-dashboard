<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return ['student_id' => Student::factory(), 'classroom_id' => Classroom::factory(), 'academic_year_id' => fn (array $attributes) => Classroom::findOrFail($attributes['classroom_id'])->academic_year_id, 'roll' => 1, 'created_by' => User::factory()];
    }
}
