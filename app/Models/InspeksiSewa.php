<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspeksiSewa extends Model
{
    protected $table = 'inspeksi_sewa';

    protected $fillable = [
        'item_pesanan_id',
        'tahap',
        'kondisi',
        'catatan',
        'pengguna_id',
    ];

    protected $casts = [
        'item_pesanan_id' => 'integer',
        'tahap' => 'string',
        'kondisi' => 'string',
    ];

    /**
     * Item pesanan terkait.
     */
    public function itemPesanan(): BelongsTo
    {
        return $this->belongsTo(ItemPesanan::class, 'item_pesanan_id');
    }

    /**
     * Pengguna yang melakukan inspeksi.
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    /**
     * Semua foto terkait inspeksi ini.
     */
    public function fotoInspeksi(): HasMany
    {
        return $this->hasMany(FotoInspeksi::class, 'inspeksi_sewa_id');
    }

    /**
     * Cek apakah inspeksi sudah lengkap (ada foto 1-6).
     */
    public function cekFotoLengkap(): bool
    {
        return $this->fotoInspeksi->count() >= 6;
    }

    /**
     * Cek kondisi.
     */
    public function kondisiLabel(): string
    {
        return match ($this->kondisi) {
            'baik' => 'Baik',
            'cacat_ringan' => 'Cacat Ringan',
            'rusak' => 'Rusak',
            default => $this->kondisi,
        };
    }

    /**
     * Cek apakah ini inspeksi serah.
     */
    public function serah(): bool
    {
        return $this->tahap === 'serah';
    }

    /**
     * Cek apakah ini inspeksi kembali.
     */
    public function kembali(): bool
    {
        return $this->tahap === 'kembali';
    }

    /**
     * Jenis penyesuaian yang bisa dibuat dari inspeksi ini.
     */
    public function jenisPenyesuaian(): array
    {
        return [
            'denda_telat' => 'Denda Telat',
            'kerusakan' => 'Kerusakan',
            'perpanjangan' => 'Perpanjangan',
        ];
    }
}
