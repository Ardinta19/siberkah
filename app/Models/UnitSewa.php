<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitSewa extends Model
{
    protected $table = 'unit_sewa';

    protected $fillable = [
        'varian_produk_id',
        'kode',
        'status',
        'catatan',
    ];

    protected $casts = [
        'varian_produk_id' => 'integer',
        'kode' => 'string',
        'status' => 'string',
    ];

    /**
     * Varian produk induk.
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'varian_produk_id');
    }

    /**
     * Semua item pesanan yang menggunakan unit ini.
     */
    public function itemPesanan(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'unit_sewa_id');
    }

    /**
     * Semua inspeksi terkait unit ini.
     */
    public function inspeksiSewa(): HasMany
    {
        return $this->hasMany(InspeksiSewa::class, 'unit_sewa_id');
    }

    /**
     * Unit aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Unit nonaktif.
     */
    public function scopeNonAktif($query)
    {
        return $query->where('status', '!=', 'aktif');
    }

    /**
     * Unit yang rusak.
     */
    public function scopeRusak($query)
    {
        return $query->where('status', 'rusak');
    }

    /**
     * Status unit untuk tampilan.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'aktif' => 'Aktif',
            'perawatan' => 'Perawatan',
            'rusak' => 'Rusak',
            'pensiun' => 'Pensiun',
            default => $this->status,
        };
    }

    /**
     * Kode unik untuk tampilan.
     */
    public function kodeLabel(): string
    {
        return 'AKR-'.$this->kode;
    }
}
