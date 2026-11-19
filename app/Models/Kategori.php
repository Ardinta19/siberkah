<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'kategoris';

    protected $fillable = [
        'nama',
        'slug',
        'icon',
        'deskripsi',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * Semua produk dalam kategori ini.
     */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    /**
     * Produk aktif dalam kategori ini.
     */
    public function produkAktif(): HasMany
    {
        return $this->produk()->where('aktif', true);
    }

    /**
     * Scope: hanya kategori aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope: urutkan berdasarkan urutan.
     */
    public function scopeTerurut($query)
    {
        return $query->orderBy('urutan');
    }
}