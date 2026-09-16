<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tutorial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'url',
        'created_by',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Helper untuk mengubah URL biasa menjadi URL Embed (YouTube/GDrive)
     */
    public function getEmbedUrlAttribute(): string
    {
        if ($this->type === 'youtube') {
            // Ekstrak ID YouTube dari berbagai format URL (watch, share, short)
            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->url, $match);
            $youtubeId = $match[1] ?? null;

            return $youtubeId ? "https://www.youtube.com/embed/{$youtubeId}" : $this->url;
        }

        if ($this->type === 'gdrive_pdf') {
            // Mengubah URL Google Drive biasa ke format /preview agar bisa di-embed di iframe
            if (str_contains($this->url, '/view')) {
                return str_replace('/view', '/preview', $this->url);
            }
            if (! str_contains($this->url, '/preview')) {
                return rtrim($this->url, '/').'/preview';
            }
        }

        return $this->url;
    }
}
