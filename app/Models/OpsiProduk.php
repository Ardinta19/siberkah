<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpsiProduk extends Model
{
    protected $table = 'opsi_produk';

    protected $fillable = [
        'produk_id',
        'nama',
        'tipe',
        'wajib',
        'urutan',
    ];

    protected $casts = [
        'produk_id' => 'integer',
        'wajib' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * Produk induk.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Semua nilai opsi untuk produk ini.
     */
    public function nilaiOpsi(): HasMany
    {
        return $this->hasMany(NilaiOpsi::class, 'opsi_produk_id');
    }

    /**
     * Semua item pesanan yang menggunakan opsi ini.
     */
    public function itemPesanan(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'opsi_produk_id');
    }

    /**
     * Semua gambar produk.
     */
    public function gambarProduk(): HasMany
    {
        return $this->hasMany(GambarProduk::class, 'produk_id')
            ->where('produk_id', $this->produk->id);
    }

    /**
     * Opsi aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Opsi wajib.
     */
    public function scopeWajib($query)
    {
        return $query->where('wajib', true);
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this->tipe) {
            'pilihan' => $this->nama,
            'teks' => 'Teks: '.$this->nama,
            'satuan' => 'Satuan: '.$this->nama,
            default => $this->nama,
        };
    }
}
