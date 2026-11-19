<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusPesanan extends Model
{
    protected $table = 'riwayat_status_pesanan';

    protected $fillable = [
        'pesanan_id',
        'status_lama',
        'status_baru',
        'kode_transisi',
        'pelaku',
        'pengguna_id',
        'catatan',
    ];

    protected $casts = [
        'pesanan_id' => 'integer',
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
     * Pelaku (staf/owner/admin).
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}