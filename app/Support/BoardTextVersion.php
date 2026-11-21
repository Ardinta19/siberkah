<?php

namespace App\Support;

final class BoardTextVersion
{
    public static function of(object $board): string
    {
        return hash('sha256', serialize([$board->judul, $board->penerima, $board->pengirim]));
    }
}
