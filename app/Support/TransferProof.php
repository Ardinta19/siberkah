<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

final class TransferProof
{
    /** Decode/re-encode a bounded image: never retain EXIF, source filename, or embedded bytes. */
    public static function prepare(UploadedFile $file): array
    {
        $bytes = file_get_contents($file->getRealPath());
        $img = @imagecreatefromstring($bytes);
        if (! $img) { throw ValidationException::withMessages(['bukti' => 'Gambar tidak dapat dibaca.']); }
        ob_start();
        $ok = imagewebp($img, null, 80);
        $webp = ob_get_clean();
        if (! $ok || ! $webp || strlen($webp) > 1048576) { throw ValidationException::withMessages(['bukti' => 'Gunakan foto bukti lebih kecil.']); }
        return ['sidik' => hash('sha256', $bytes), 'gambar_terenkripsi' => Crypt::encryptString(base64_encode($webp))];
    }
}
