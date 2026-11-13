<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        $serial = fake()->unique()->numberBetween(1, 99999);

        return ['student_number' => sprintf('NGHS202706%05d', $serial), 'admission_year' => 2027, 'admission_grade' => 6,
            'admission_serial' => $serial, 'name_bn' => 'শিক্ষার্থী', 'name_en' => fake()->name(), 'details' => [],
            'guardian' => ['name' => 'Guardian', 'phone' => '01712345678', 'relationship' => 'Mother']];
    }
}
