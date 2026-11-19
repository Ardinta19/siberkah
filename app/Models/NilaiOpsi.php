<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NilaiOpsi extends Model
{
    protected $table = 'nilai_opsi';

    protected $fillable = [
        'opsi_produk_id',
        'label',
        'harga_tambahan',
        'stok',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'opsi_produk_id' => 'integer',
        'harga_tambahan' => 'integer',
        'stok' => 'integer',
        'urutan' => 'integer',
        'aktif' => 'boolean',
    ];

    /**
     * Opsi induk.
     */
    public function opsiProduk(): BelongsTo
    {
        return $this->belongsTo(OpsiProduk::class, 'opsi_produk_id');
    }

    /**
     * Semua item pesanan yang menggunakan nilai opsi ini.
     */
    public function itemPesanan(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'nilai_opsi_id');
    }

    /**
     * Nilai opsi aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Nilai opsi yang wajib dipilih.
     */
    public function scopeWajib($query)
    {
        return $query->where('wajib', true);
    }

    /**
     * Nilai opsi aktif & wajib.
     */
    public function scopeAktifDanWajib($query)
    {
        return $query->where('aktif', true)->where('wajib', true);
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        if ($this->tipe) {
            return match ($this->tipe) {
                'pilihan' => $this->label,
                'teks' => 'Teks: '.$this->label,
                'satuan' => 'Satuan: '.$this->label,
                default => $this->label,
            };
        }

        return $this->label;
    }
}
