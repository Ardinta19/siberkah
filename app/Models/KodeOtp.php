<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeOtp extends Model
{
    protected $table = 'kode_otp';
    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = null;
    protected $hidden = ['kode_hash'];
    protected $fillable = ['nomor_wa', 'kode_hash', 'alamat_ip', 'kedaluwarsa_pada', 'dipakai_pada', 'status_kirim'];
    protected $casts = ['kedaluwarsa_pada' => 'datetime', 'dipakai_pada' => 'datetime'];
}
