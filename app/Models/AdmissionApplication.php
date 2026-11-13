<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionApplication extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'academic_year_id', 'grade', 'name_bn', 'name_en', 'preferred_shift', 'preferred_group', 'details', 'guardian', 'photo_path', 'status', 'selected_shift', 'published_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'guardian' => 'array', 'grade' => 'integer', 'published_at' => 'datetime'];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
