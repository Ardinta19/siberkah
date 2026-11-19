<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\UnitSewa;

class VarianProduk extends Model
{
    protected $table = 'varian_produk';

    protected $fillable = [
        'produk_id',
        'nama',
        'awalan_kode',
        'harga_tambahan',
        'aktif',
    ];

    protected $casts = [
        'produk_id' => 'integer',
        'harga_tambahan' => 'integer',
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
     * Semua unit sewa untuk varian ini.
     */
    public function unitSewa(): HasMany
    {
        return $this->hasMany(UnitSewa::class, 'varian_produk_id');
    }

    /**
     * Semua item pesanan untuk varian ini.
     */
    public function itemPesanan(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'varian_produk_id');
    }

    /**
     * Semua opsi untuk varian ini.
     */
    public function opsiProduk(): HasMany
    {
        return $this->hasMany(OpsiProduk::class, 'produk_id')
            ->where('produk_id', $this->produk->id);
    }

    /**
     * Varian aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Varian yang digunakan.
     */
    public function scopeDigunakan($query)
    {
        return $query->whereHas('itemPesanan');
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this->awalan_kode) {
            null => $this->nama,
            default => $this->nama.' ['.$this->awalan_kode.']',
        };
    }
}
