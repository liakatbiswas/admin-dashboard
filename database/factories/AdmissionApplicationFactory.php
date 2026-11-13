<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdmissionApplicationFactory extends Factory
{
    public function definition(): array
    {
        return ['number' => 'NGHS-APP-2027-'.fake()->unique()->numerify('#####'), 'academic_year_id' => AcademicYear::factory(), 'grade' => 6, 'name_bn' => 'শিক্ষার্থী', 'name_en' => fake()->name(), 'preferred_shift' => 'Morning', 'details' => ['gender' => 'Female', 'birth_date' => '2015-01-01', 'religion' => 'Islam', 'present_address' => 'Dhaka'], 'guardian' => ['name' => 'Guardian', 'relationship' => 'Mother', 'phone' => '01712345678']];
    }
}
