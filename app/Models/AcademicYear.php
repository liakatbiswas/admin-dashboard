<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'is_current'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'is_current' => 'boolean'];
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
