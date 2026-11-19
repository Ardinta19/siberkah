<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyesuaianPesanan extends Model
{
    protected $table = 'penyesuaian_pesanan';

    protected $fillable = [
        'pesanan_id',
        'jenis',
        'keterangan',
        'nominal',
        'pengguna_id',
        'dibuat_pada',
    ];

    protected $casts = [
        'pesanan_id' => 'integer',
        'nominal' => 'integer',
        'pengguna_id' => 'integer',
        'dibuat_pada' => 'datetime',
    ];

    /**
     * Pesanan terkait.
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    /**
     * Pelaku (owner/admin).
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}