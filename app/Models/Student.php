<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'admission_application_id', 'guardian_user_id', 'student_number', 'admission_year', 'admission_grade', 'admission_serial', 'name_bn', 'name_en', 'details', 'guardian', 'photo_path'];

    protected static function booted(): void
    {
        static::updating(function (Student $student): void {
            if ($student->isDirty(['student_number', 'admission_year', 'admission_grade', 'admission_serial'])) {
                throw ValidationException::withMessages(['student_number' => __('The permanent Student ID and admission identity cannot be changed.')]);
            }
        });
    }

    protected function casts(): array
    {
        return ['details' => 'array', 'guardian' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardianUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
