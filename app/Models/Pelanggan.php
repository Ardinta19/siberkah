<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = [
        'nomor_wa',
        'nama',
        'aktif',
        'nomor_terverifikasi_pana',
        'masuk_terakhir_pana',
        'dihapus_pana',
        'persetujuan_kebijakan',
        'versi_kebijakan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'nomor_terverifikasi_pana' => 'datetime',
        'masuk_terakhir_pana' => 'datetime',
        'dihapus_pana' => 'datetime',
        'persetujuan_kebijakan' => 'boolean',
        'versi_kebijakan' => 'string',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'role_label',
        'is_verified',
        'is_active',
        'formatted_wa',
        'is_onboarded',
    ];

    /**
     * Get the user associated with this pelanggan (many-to-many).
     */
    public function pengguna(): BelongsToMany
    {
        return $this->belongsToMany(Pengguna::class, 'pelanggan_user_pivot', 'pelanggan_id', 'user_id');
    }

    /**
     * Get the pesanan associated with this pelanggan.
     */
    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'pelanggan_id');
    }

    /**
     * Get the log aktivitas associated with this pelanggan.
     */
    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'pelanggan_id');
    }

    /**
     * Get the formatted WhatsApp number.
     */
    public function getFormattedWaAttribute(): string
    {
        if ($this->nomor_wa === null) {
            return '-';
        }
        if (str_starts_with($this->nomor_wa, '628')) {
            return '+'.$this->nomor_wa;
        }
        if (str_starts_with($this->nomor_wa, '08')) {
            return '+62'.substr($this->nomor_wa, 2);
        }

        return '+'.$this->nomor_wa;
    }

    /**
     * Check if phone number is in valid Indonesian format.
     */
    public function isValidPhoneFormat(): bool
    {
        if ($this->nomor_wa === null) {
            return false;
        }

        return preg_match('/^(628|08)[0-9]{6,12}$/', $this->nomor_wa) === 1;
    }

    /**
     * Check if phone number has been verified.
     */
    public function isVerified(): bool
    {
        return ! is_null($this->nomor_terverifikasi_pana);
    }

    /**
     * Check if pelanggan is active.
     */
    public function isActive(): bool
    {
        return $this->aktif && ! is_null($this->dihapus_pana);
    }

    /**
     * Check if pelanggan is onboarded (sudah setuju kebijakan + pernah masuk).
     */
    public function isOnboarded(): bool
    {
        return $this->persetujuan_kebijakan && ! is_null($this->versi_kebijakan) && $this->masuk_terakhir_pana !== null;
    }

    /**
     * Check if pelanggan can place orders.
     */
    public function canPlaceOrders(): bool
    {
        return $this->aktif && $this->isVerified() && $this->isOnboarded();
    }

    /**
     * Get the display name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->nomor_wa === null) {
            return 'Pelanggan tanpa nomor WA';
        }

        return 'Pelanggan '.substr($this->nomor_wa, -4);
    }

    /**
     * Scope: hanya pelanggan aktif.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope: hanya pelanggan terverifikasi.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeTerverifikasi($query)
    {
        return $query->whereNotNull('nomor_terverifikasi_pana');
    }

    /**
     * Scope: hanya pelanggan aktif & terverifikasi.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeAktifDanTerverifikasi($query)
    {
        return $query->where('aktif', true)
            ->whereNotNull('nomor_terverifikasi_pana');
    }

    /**
     * Scope: hanya pelanggan yang belum dihapus.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeTidakDihapus($query)
    {
        return $query->whereNull('dihapus_pana');
    }

    /**
     * Scope: hanya pelanggan yang bisa memesan.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeBisaMemesan($query)
    {
        return $query->where('aktif', true)
            ->whereNotNull('nomor_terverifikasi_pana')
            ->where('persetujuan_kebijakan', true);
    }
}
