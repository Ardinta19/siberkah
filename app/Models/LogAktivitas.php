<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    protected $fillable = [
        'pengguna_id',
        'pelanggan_id',
        'aksi',
        'subjek_tabel',
        'subjek_id',
        'sebelum',
        'sesudah',
        'alamat_ip',
        'dibuat_pada',
    ];

    protected $casts = [
        'pengguna_id' => 'integer',
        'pelanggan_id' => 'integer',
        'subjek_id' => 'integer',
        'sebelum' => 'json',
        'sesudah' => 'json',
        'dibuat_pada' => 'datetime',
    ];
}