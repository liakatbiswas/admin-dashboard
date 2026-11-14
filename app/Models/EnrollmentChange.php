<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentChange extends Model
{
    use HasFactory;

    protected $fillable = ['enrollment_id', 'type', 'before', 'after', 'effective_date', 'reason', 'override_reason', 'changed_by'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'effective_date' => 'date'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }
}
