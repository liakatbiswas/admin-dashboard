<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    public const STATUSES = ['Present', 'Absent', 'Approved Leave'];

    protected $fillable = ['student_id', 'enrollment_id', 'classroom_id', 'date', 'roll', 'group', 'status', 'version', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'roll' => 'integer', 'version' => 'integer'];
    }

    protected function date(): Attribute
    {
        return Attribute::make(set: fn (string|\DateTimeInterface $value) => Carbon::parse($value)->toDateString());
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
