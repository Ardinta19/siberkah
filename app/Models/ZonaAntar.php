<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaAntar extends Model
{
    protected $table = 'zona_antar';

    protected $fillable = [
        'nama',
        'jarak_maks_km',
        'ongkir',
        'aktif',
    ];

    protected $casts = [
        'jarak_maks_km' => 'decimal:1',
        'ongkir' => 'integer',
        'aktif' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}