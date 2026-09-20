<?php

namespace App\Models;

use App\Services\DriveLink;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_version',
        'student_id',
        'meeting_id',
        'submission_link',
        'file_path',
        'original_filename',
        'file_size',
        'notes',
        'final_task_id',
        'is_final',
        'aslab_status',
        'laboran_status',
        'dosen_status',
        'is_completed',
        'first_upload_at',
        'last_upload_at',
        'aslab_acc_at',
        'laboran_acc_at',
        'dosen_acc_at',
    ];

    protected $casts = [
        'first_upload_at' => 'datetime',
        'last_upload_at' => 'datetime',
        'aslab_acc_at' => 'datetime',
        'laboran_acc_at' => 'datetime',
        'dosen_acc_at' => 'datetime',
        'is_completed' => 'boolean',
        'is_final' => 'boolean',
    ];

    // Relasi ke Mahasiswa
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Relasi ke Pertemuan (Jika ini laprak mingguan)
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    // Relasi ke Tugas Final (Jika ini pengumpulan final)
    public function finalTask()
    {
        return $this->belongsTo(FinalTask::class);
    }

    // Relasi ke Riwayat (Sangat penting untuk melihat semua link revisi)
    public function histories()
    {
        return $this->hasMany(SubmissionHistory::class)->orderBy('iteration', 'desc');
    }

    public function documentUrl(): ?string
    {
        return $this->file_path ? route('submissions.file', $this) : $this->submission_link;
    }

    public function previewUrl(): ?string
    {
        return $this->file_path ? $this->documentUrl() : DriveLink::preview($this->submission_link);
    }

    public function studentStatus(): string
    {
        $statuses = [$this->aslab_status, $this->laboran_status];

        if ($this->is_final) {
            $statuses[] = $this->dosen_status;
        }

        if (in_array('Ditolak', $statuses, true)) {
            return 'Ditolak';
        }

        if (in_array('Revisi', $statuses, true)) {
            return 'Revisi';
        }

        if ($this->is_completed) {
            return 'Diterima';
        }

        return 'Menunggu pemeriksaan';
    }

    public function canResubmit(): bool
    {
        return in_array($this->studentStatus(), ['Revisi', 'Ditolak'], true);
    }
}
