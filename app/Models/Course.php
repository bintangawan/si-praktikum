<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Course extends Model
{
    protected $fillable = [
        'semester_id',
        'is_archived',
        'course_name',
        'class_group',
        'target_semester',
        'dosen_id',
        'laboran_id',
        'aslab_id',
        'enrollment_code',
    ];

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Course $course): void {
            if ($course->slug && ! $course->isDirty(['course_name', 'class_group'])) {
                return;
            }

            $base = Str::slug(trim($course->course_name.' '.$course->class_group)) ?: 'kelas';
            $slug = $base;
            $counter = 2;

            while (static::query()
                ->where('slug', $slug)
                ->when($course->exists, fn ($query) => $query->where($course->getKeyName(), '!=', $course->getKey()))
                ->exists()) {
                $slug = $base.'-'.$counter++;
            }

            $course->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Relasi ke Semester
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    // Relasi ke Dosen
    public function dosen()
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    // Relasi ke Laboran
    public function laboran()
    {
        return $this->belongsTo(User::class, 'laboran_id');
    }

    // Relasi ke Aslab
    public function aslab()
    {
        return $this->belongsTo(User::class, 'aslab_id');
    }

    // Relasi ke Mahasiswa (Many to Many)
    public function students()
    {
        // Pastikan table pivotnya benar (misal: course_user atau course_student)
        return $this->belongsToMany(User::class, 'course_user', 'course_id', 'user_id');
    }

    // Relasi ke Pertemuan
    public function meetings()
    {
        return $this->hasMany(Meeting::class)->orderBy('meeting_number', 'asc');
    }

    public function finalTask()
    {
        // Pastikan nama foreign key di tabel final_tasks adalah course_id
        return $this->hasOne(FinalTask::class, 'course_id');
    }

    public function grades()
    {
        return $this->hasMany(CourseGrade::class);
    }

    public function isArchived(): bool
    {
        if ($this->is_archived) {
            return true;
        }

        if ($this->relationLoaded('semester')) {
            return ! (bool) $this->semester?->is_active;
        }

        return ! $this->semester()->where('is_active', true)->exists();
    }
}
