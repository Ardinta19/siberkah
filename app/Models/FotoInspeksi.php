<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoInspeksi extends Model
{
    protected $table = 'foto_inspeksi';

    protected $fillable = [
        'inspeksi_sewa_id',
        'urutan',
        'jalur_berkas',
        'aktif',
    ];

    protected $casts = [
        'inspeksi_sewa_id' => 'integer',
        'urutan' => 'integer',
        'aktif' => 'boolean',
    ];

    /**
     * Inspeksi terkait.
     */
    public function inspeksiSewa(): BelongsTo
    {
        return $this->belongsTo(InspeksiSewa::class, 'inspeksi_sewa_id');
    }

    /**
     * Semua foto inspeksi untuk satu inspeksi (urutan 1-6).
     */
    public static function cekUrutan($inspeksiId): bool
    {
        $count = static::where('inspeksi_sewa_id', $inspeksiId)->count();

        return $count >= 6;
    }

    /**
     * Cek urutan foto (1-6).
     */
    public function cekUrutValid(int $urutan): bool
    {
        return $urutan >= 1 && $urutan <= 6;
    }

    /**
     * Label urutan.
     */
    public function urutanLabel(): string
    {
        return match ($this->urutan) {
            1 => 'Pertama',
            2 => 'Kedua',
            3 => 'Ketiga',
            4 => 'Keempat',
            5 => 'Kelima',
            6 => 'Keenam',
            default => 'Urutan '.$this->urutan,
        };
    }

    /**
     * Cek apakah foto utama.
     */
    public function fotoUtama(): bool
    {
        return $this->urutan === 1;
    }
}
