<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        return ['year' => fake()->unique()->numberBetween(2000, 2200)];
    }
}
