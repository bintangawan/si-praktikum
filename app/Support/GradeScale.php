<?php

namespace App\Support;

class GradeScale
{
    public static function letter(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 86 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'E',
        };
    }
}
