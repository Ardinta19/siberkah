<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KapasitasProduksiHarian extends Model
{
    protected $table = 'kapasitas_produksi_harian';

    protected $fillable = [
        'tanggal',
        'kelompok_kapasitas',
        'batas',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'batas' => 'integer',
    ];

    /**
     * Kapasitas produk induk.
     */
    public function kelompokProduk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'kelompok_kapasitas');
    }

    /**
     * Scope: hanya batas harian untuk tanggal tertentu.
     */
    public function scopeHariIni($query)
    {
        return $query->where('tanggal', now()->toDateString());
    }

    /**
     * Scope: hanya batas yang sudah ditetapkan.
     */
    public function scopeTetapkan($query)
    {
        return $query->whereNotNull('batas');
    }

    /**
     * Scope: urut berdasarkan tanggal.
     */
    public function scopeUrut($query)
    {
        return $query->orderBy('tanggal');
    }

    /**
     * Cek apakah kapasitas sudah terpenuhi untuk tanggal ini.
     */
    public function cekKapasitasHariIni(int $jumlah): bool
    {
        $hariIni = static::where('tanggal', now()->toDateString())->first();
        if (! $hariIni) {
            return true; // Belum ada setting, anggap bisa
        }

        return ($hariIni->batas ?? 0) >= $jumlah;
    }
}
