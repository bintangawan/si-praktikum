<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionHistory extends Model
{
    protected $fillable = [
        'submission_id',
        'drive_link',
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
}
