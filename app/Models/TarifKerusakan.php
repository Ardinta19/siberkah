<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifKerusakan extends Model
{
    protected $table = 'tarif_kerusakan';

    protected $fillable = [
        'kode',
        'nama',
        'tarif',
        'satuan',
    ];

    protected $casts = [
        'tarif' => 'integer',
    ];
}