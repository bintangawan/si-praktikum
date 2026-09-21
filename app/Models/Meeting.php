<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'course_id',
        'meeting_number',
        'title',
        'description',
        'module_drive_link',
        'deadline',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    // Relasi: Pertemuan milik sebuah Kelas
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Relasi: Pertemuan memiliki banyak Absensi
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Relasi: Pertemuan memiliki banyak Pengumpulan (Submissions)
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
