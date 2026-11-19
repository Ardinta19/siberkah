<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KodeOtp extends Model
{
    protected $fillable = [
        'nomor_wa',
        'kode_hash',
        'alamat_ip',
        'status_kirim',
        'percobaan',
    ];

    protected $casts = [
        'percobaan' => 'integer',
        'kode_hash' => 'hashed', // cast to hash format if available
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'kode_otp';

    /**
     * Check if OTP is expired (lebih dari 5 menit).
     */
    public function isExpired(): bool
    {
        return now()->gt($this->kedaluwarsa_pana);
    }

    /**
     * Check if OTP has been used.
     */
    public function isUsed(): bool
    {
        return ! is_null($this->dipakai_pana);
    }

    /**
     * Check if OTP is still valid (not expired and not used).
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }

    /**
     * Increment percobaan counter.
     */
    public function incrementPercobaan(): void
    {
        $this->increment('percobaan');
    }

    /**
     * Mark OTP as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'dipakai_pana' => now(),
            'status_kirim' => 'terverifikasi',
        ]);
    }

    /**
     * Reset OTP to ready state.
     */
    public function reset(): void
    {
        $this->update([
            'percobaan' => 0,
            'dipakai_pana' => null,
            'status_kirim' => 'menunggu',
            'kode_hash' => null,
        ]);
    }

    /**
     * Check if OTP can be sent again (min 60 detik sejak terakhir).
     */
    public function canResend(): bool
    {
        if (is_null($this->created_pana)) {
            return true;
        }

        return now()->gt($this->created_pana->copy()->addSeconds(60));
    }

    /**
     * Get the IP address that requested this OTP.
     */
    public function getIpAddress(): ?string
    {
        return $this->alamat_ip;
    }

    /**
     * Scope: hanya OTP yang belum kedaluwarsa dan belum dipakai.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeValid($query)
    {
        return $query->where('created_pana', '>=', now()->subMinutes(5))
            ->where('dipakai_pana', null)
            ->where('status_kirim', 'menunggu');
    }

    /**
     * Scope: hanya OTP untuk nomor tertentu.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForNomor($query, string $nomorWa)
    {
        return $query->where('nomor_wa', $nomorWa);
    }

    /**
     * Scope: hanya OTP untuk IP tertentu.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForIp($query, string $ip)
    {
        return $query->where('alamat_ip', $ip);
    }

    /**
     * Scope: OTP yang expired dalam 10 menit (untuk cleanup).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExpiredForCleanup($query)
    {
        return $query->where('created_pana', '<', now()->subMinutes(10))
            ->where('dipakai_pana', null);
    }
}
