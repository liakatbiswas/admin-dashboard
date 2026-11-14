<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionFactory extends Factory
{
    public function definition(): array
    {
        return ['attendance_id' => Attendance::factory()->state(['version' => 2]), 'old_status' => 'Absent',
            'new_status' => 'Present', 'reason' => 'Checked the signed attendance register', 'version' => 2, 'corrected_by' => User::factory()];
    }
}
