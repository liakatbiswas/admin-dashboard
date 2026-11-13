<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = ['academic_year_id', 'grade', 'shift', 'section', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return ['grade' => 'integer', 'capacity' => 'integer', 'is_active' => 'boolean'];
    }

    public const GRADES = [6, 7, 8, 9, 10];

    public const SHIFTS = ['Morning', 'Day'];

    public const GROUPS = ['Science', 'Humanities', 'Business Studies'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function allowsGroup(?string $group): bool
    {
        return $this->grade >= 9 ? in_array($group, self::GROUPS, true) : $group === null;
    }
}
