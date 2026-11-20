<?php

namespace App\Support;

final class InspectionPath
{
    public static function valid(int $inspection, string $path): bool
    {
        return preg_match('~^inspeksi/'.$inspection.'/[A-Za-z0-9_-]+\.(?:png|jpg|jpeg|webp)$~D', $path) === 1;
    }
}
