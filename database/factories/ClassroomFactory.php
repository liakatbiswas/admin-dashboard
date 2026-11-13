<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return ['academic_year_id' => AcademicYear::factory(), 'grade' => 6, 'shift' => 'Morning', 'section' => 'A', 'capacity' => 50, 'is_active' => true];
    }
}
