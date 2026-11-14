<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'academic_year_id', 'classroom_id', 'roll', 'group', 'override_reason', 'created_by'];

    protected function casts(): array
    {
        return ['roll' => 'integer'];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return array{classroom_id: int, academic_year: int, grade: int, shift: string, section: string, roll: int, group: ?string} */
    public function placementOn(string $date): array
    {
        $nextChange = $this->getRelationValue('changes')->sortBy([['effective_date', 'asc'], ['id', 'asc']])
            ->first(fn (EnrollmentChange $change) => $change->effective_date->toDateString() > $date);
        if ($nextChange) {
            return $nextChange->before;
        }

        return ['classroom_id' => $this->classroom_id, 'academic_year' => $this->classroom->academicYear->year,
            'grade' => $this->classroom->grade, 'shift' => $this->classroom->shift, 'section' => $this->classroom->section,
            'roll' => $this->roll, 'group' => $this->group];
    }

    public function changes(): HasMany
    {
        return $this->hasMany(EnrollmentChange::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
