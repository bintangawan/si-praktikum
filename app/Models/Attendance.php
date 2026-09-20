<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    public static function label(?string $status): string
    {
        return match (strtoupper(trim($status ?? ''))) {
            'H', 'HADIR' => 'Hadir',
            'S', 'SAKIT' => 'Sakit',
            'I', 'IZIN' => 'Izin',
            'TK', 'TANPA KETERANGAN', 'ALPA', 'ALPHA' => 'Tanpa Keterangan',
            default => 'Belum Presensi',
        };
    }

    protected $fillable = [
        'meeting_id',
        'student_id',
        'status',
        'attendance_date',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    // Relasi ke Pertemuan
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    // Relasi ke Mahasiswa (User)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
