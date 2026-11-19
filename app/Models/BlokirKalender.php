<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlokirKalender extends Model
{
    protected $table = 'blokir_kalender';

    protected $fillable = [
        'tanggal',
        'produk_id',
        'alasan',
        'pengguna_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'produk_id' => 'integer',
        'pengguna_id' => 'integer',
    ];

    /**
     * Produk yang diblokir (nullable = seluruh toko).
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Pengguna yang membuat blokir.
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}