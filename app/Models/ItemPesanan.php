<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item Pesanan - mewakili setiap baris item dalam pesanan
 * Setiap pesanan bisa memiliki banyak item (varian, opsi, dll)
 */
class ItemPesanan extends Model
{
    protected $table = 'item_pesanan';

    protected $fillable = [
        'pesanan_id',
        'produk_id',
        'varian_produk_id',
        'opsi_produk_id',
        'nilai_opsi_id',
        'qty',
        'harga_satuan',
        'harga_total',
        'catatan',
    ];

    /**
     * Get the pesanan that the item belongs to.
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    /**
     * Get the produk that the item belongs to (if any).
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Get the variant if this item has a variant.
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'varian_produk_id');
    }

    /**
     * Get the option if this item has an option.
     */
    public function opsiProduk(): BelongsTo
    {
        return $this->belongsTo(OpsiProduk::class, 'opsi_produk_id');
    }

    /**
     * Get the nilai opsi if this item has a nilai opsi.
     */
    public function nilaiOpsi(): BelongsTo
    {
        return $this->belongsTo(NilaiOpsi::class, 'nilai_opsi_id');
    }
}
