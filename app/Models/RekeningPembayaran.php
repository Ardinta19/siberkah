<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rekening pembayaran - rekening bank untuk pembayaran pelanggan
 */
class RekeningPembayaran extends Model
{
    protected $table = 'rekening_pembayaran';

    protected $fillable = [
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
        'aktif',
        'urutan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeTerurut($query)
    {
        return $query->orderBy('urutan');
    }
}
