<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalTask extends Model
{
    protected $fillable = [
        'course_id',
        'description',
        'deadline',
    ];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }

    // Relasi ke Kelas
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Relasi ke Pengumpulan (Submissions)
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
