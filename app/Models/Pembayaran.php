<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'pesanan_id',
        'rekening_pembayaran_id',
        'jenis',
        'status',
        'tagihan',
        'kode_unik',
        'nominal_diharapkan',
        'nominal_tertunda',
        'nominal_diterima',
        'dikonfirmasi_pelanggan_pana',
        'diterima_pana',
        'pengguna_id',
        'diverifikasi_pana',
        'catatan_verifikasi',
    ];

    protected $casts = [
        'pesanan_id' => 'integer',
        'rekening_pembayaran_id' => 'integer',
        'jenis' => PaymentType::class,
        'status' => PaymentStatus::class,
        'tagihan' => 'integer',
        'kode_unik' => 'integer',
        'nominal_diharapkan' => 'integer',
        'nominal_tertunda' => 'integer',
        'nominal_diterima' => 'integer',
        'dikonfirmasi_pelanggan_pana' => 'datetime',
        'diterima_pana' => 'datetime',
        'pengguna_id' => 'integer',
        'diverifikasi_pana' => 'datetime',
        'catatan_verifikasi' => 'string',
    ];

    /**
     * Pesanan terkait.
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    /**
     * Rekening pembayaran terkait.
     */
    public function rekeningPembayaran(): BelongsTo
    {
        return $this->belongsTo(RekeningPembayaran::class, 'rekening_pembayaran_id');
    }

    /**
     * Cek apakah kode unik valid (1-999).
     */
    public function cekKodeUnikValid(): bool
    {
        return $this->kode_unik !== null && $this->kode_unik >= 1 && $this->kode_unik <= 999;
    }

    /**
     * Cek apakah nominal diharapkan benar (tagihan + kode unik).
     */
    public function cekNominalDiharapkan(): bool
    {
        return $this->nominal_diharapkan == $this->tagihan + ($this->kode_unik ?? 0);
    }

    /**
     * Cek apakah status bisa diverifikasi.
     */
    public function bisaDiverifikasi(): bool
    {
        return in_array($this->status, [PaymentStatus::MENUNGGU, PaymentStatus::KEDALUWARSA]);
    }

    /**
     * Apakah ini pembayaran DP (total < 250k wajib lunas).
     */
    public function adalahDP(): bool
    {
        return $this->jenis === PaymentType::DP && $this->tagihan < 250000;
    }

    /**
     * Status sudah terverifikasi.
     */
    public function sudahTerverifikasi(): bool
    {
        return $this->status === PaymentStatus::TERVERIFIKASI;
    }

    /**
     * Apakah ini pembayaran refund.
     */
    public function adalahRefund(): bool
    {
        return $this->jenis === PaymentType::REFUND;
    }

    /**
     * Status bisa dibayar lunas (total < 250k) atau DP (total >= 250k).
     */
    public function bisaLunas(): bool
    {
        if ($this->tagihan < 250000) {
            return $this->status !== PaymentStatus::DITOLAK; // wajib lunas
        }

        // total >= 250k: bisa DP 50% atau lunas
        return true;
    }

    /**
     * Cek apakah pembayaran ini terkait dengan refund sebelumnya.
     */
    public function adalahRefundRef(): bool
    {
        return ! is_null($this->nominal_refund);
    }

    /**
     * Scope: pembayaran menunggu verifikasi.
     */
    public function scopeMenunggu($query)
    {
        return $query->where('status', PaymentStatus::MENUNGGU);
    }

    /**
     * Scope: pembayaran sudah diverifikasi.
     */
    public function scopeTerverifikasi($query)
    {
        return $query->where('status', PaymentStatus::TERVERIFIKASI);
    }

    /**
     * Scope: pembayaran ditolak.
     */
    public function scopeDitolak($query)
    {
        return $query->where('status', PaymentStatus::DITOLAK);
    }

    /**
     * Scope: pembayaran kedaluwarsa.
     */
    public function scopeKedaluwarsa($query)
    {
        return $query->where('status', PaymentStatus::KEDALUWARSA);
    }

    /**
     * Label status untuk tampilan.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            PaymentStatus::MENUNGGU => 'Menunggu verifikasi',
            PaymentStatus::TERVERIFIKASI => 'Terverifikasi',
            PaymentStatus::DITOLAK => 'Ditolak',
            PaymentStatus::KEDALUWARSA => 'Kedaluwarsa',
        };
    }

    /**
     * Jenis pembayaran untuk tampilan.
     */
    public function jenisLabel(): string
    {
        return match ($this->jenis) {
            PaymentType::DP => 'DP',
            PaymentType::PELUNASAN => 'Pelunasan',
            PaymentType::PENUH => 'Lunas',
            PaymentType::REFUND => 'Refund',
        };
    }
}
