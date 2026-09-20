<?php

namespace App\Models;

use App\Services\DriveLink;
use Illuminate\Database\Eloquent\Model;

class SubmissionHistory extends Model
{
    protected $fillable = [
        'document_version',
        'submission_id',
        'drive_link',
        'file_path',
        'original_filename',
        'file_size',
        'iteration',
        'feedback',
        'action_type',
        'reviewed_by',
    ];

    // Relasi kembali ke induknya
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    // Relasi ke User yang memberikan review (Aslab/Laboran/Dosen)
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documentUrl(): ?string
    {
        return $this->file_path ? route('submission-histories.file', $this) : $this->drive_link;
    }

    public function previewUrl(): ?string
    {
        return $this->file_path ? $this->documentUrl() : DriveLink::preview($this->drive_link);
    }
}
