<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GambarProduk extends Model
{
    protected $table = 'gambar_produk';

    protected $fillable = [
        'produk_id',
        'varian_produk_id',
        'jalan_berkas',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'produk_id' => 'integer',
        'varian_produk_id' => 'integer',
        'urutan' => 'integer',
        'aktif' => 'boolean',
    ];

    /**
     * Produk induk.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Varian produk (jika ada).
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'varian_produk_id');
    }

    /**
     * Semua gambar untuk produk ini.
     */
    public function galeri(): HasMany
    {
        return $this->hasMany(static::class, 'produk_id')->orderBy('urutan');
    }

    /**
     * Gambar aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Gambar utama (urutan 1).
     */
    public function scopeUtama($query)
    {
        return $this->where('urutan', 1)->where('aktif', true);
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this->urutan) {
            1 => 'Utama',
            default => 'Gambar '.$this->urutan,
        };
    }
}
