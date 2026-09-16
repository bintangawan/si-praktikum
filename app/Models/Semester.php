<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // Relasi: Satu semester memiliki banyak kelas
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    // Scope untuk mempermudah memanggil semester yang sedang aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
