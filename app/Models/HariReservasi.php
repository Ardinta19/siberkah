<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HariReservasi extends Model
{
    protected $table = 'hari_reservasi';

    protected $fillable = [
        'unit_sewa_id',
        'tanggal',
        'pesanan_id',
        'item_pesanan_id',
        'created_pana',
    ];

    protected $casts = [
        'unit_sewa_id' => 'integer',
        'tanggal' => 'date',
        'pesanan_id' => 'integer',
        'item_pesanan_id' => 'integer',
    ];

    /**
     * Unit sewa terkait.
     */
    public function unitSewa(): BelongsTo
    {
        return $this->belongsTo(UnitSewa::class, 'unit_sewa_id');
    }

    /**
     * Pesanan terkait.
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    /**
     * Item pesanan terkait.
     */
    public function itemPesanan(): BelongsTo
    {
        return $this->belongsTo(ItemPesanan::class, 'item_pesanan_id');
    }

    /**
     * Cek apakah reservasi valid (unit bebas).
     */
    public function cekReservasiValid(): bool
    {
        return $this->unitSewa->status === 'aktif' && is_null($this->pesanan_id);
    }

    /**
     * Cek apakah tanggal sudah dipesan.
     */
    public static function tanggalSudahDipesan($tanggal): bool
    {
        return static::where('tanggal', $tanggal)->whereNotNull('pesanan_id')->exists();
    }

    /**
     * Cek ketersediaan unit untuk tanggal tertentu.
     */
    public static function cekKetersediaan($tanggal, $excludeUnitId = null): int
    {
        $pesan = static::where('tanggal', $tanggal)->whereNotNull('pesanan_id')->count();

        return $pesan;
    }

    /**
     * Scope: hanya reservasi aktif.
     */
    public function scopeAktif($query)
    {
        return $query->whereNull('pesanan_id');
    }

    /**
     * Scope: reservasi untuk pemesanan tertentu.
     */
    public function scopeUntukPesanan($query, $pesananId)
    {
        return $query->where('pesanan_id', $pesananId);
    }
}
