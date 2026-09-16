<?php

namespace App\Enums;

enum UserRole: string
{
    case DOSEN = 'Dosen';
    case MAHASISWA = 'Mahasiswa';
    case LABORAN = 'Laboran';
    case ASLAB = 'Aslab';

    public static function normalize(?string $role): ?self
    {
        $role = trim((string) $role);

        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $role) === 0) {
                return $case;
            }
        }

        return null;
    }
}
