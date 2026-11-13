<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::firstOrCreate(['year' => (int) now('Asia/Dhaka')->format('Y')]);
    }
}
