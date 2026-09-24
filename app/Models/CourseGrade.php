<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseGrade extends Model
{
    protected $fillable = ['course_id', 'student_id', 'uts_score', 'uas_score'];

    protected function casts(): array
    {
        return ['uts_score' => 'decimal:2', 'uas_score' => 'decimal:2'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
