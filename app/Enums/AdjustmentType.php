<?php

namespace App\Enums;

/**
 * Jenis penyesuaian pesanan sesuai BR-13, BR-10, BR-11.
 * Dapat bernilai negatif (potongan) atau positif (denda/tambah).
 * Hanya Owner yang boleh mengubah nominal final.
 */
enum AdjustmentType: string
{
    case DENDA_TELAT = 'denda_telat';
    case KERUSAKAN = 'kerusakan';
    case PERPANJANGAN = 'perpanjangan';
    case DISKON_MANUAL = 'diskon_manual';
    case BIAYA_ADMIN_REFUND = 'biaya_admin_refund';

    /**
     * Apakah jenis ini bisa membuat saldo menjadi negatif (potongan).
     */
    public function canMakeNegative(): bool
    {
        return match ($this) {
            self::DENDA_TELAT,
            self::KERUSAKAN,
            self::PERPANJANGAN,
            self::DISKON_MANUAL => true,
            self::BIAYA_ADMIN_REFUND => false,
        };
    }

    /**
     * Label untuk tampilan UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::DENDA_TELAT => 'Denda Telat',
            self::KERUSAKAN => 'Kerusakan',
            self::PERPANJANGAN => 'Perpanjangan',
            self::DISKON_MANUAL => 'Diskon Manual',
            self::BIAYA_ADMIN_REFUND => 'Biaya Admin Refund',
        };
    }

    /**
     * Warna badge UI.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::DENDA_TELAT => 'warning',
            self::KERUSAKAN => 'danger',
            self::PERPANJANGAN => 'secondary',
            self::DISKON_MANUAL => 'success',
            self::BIAYA_ADMIN_REFUND => 'warning',
        };
    }

    /**
     * Apakah jenis ini butuh keterangan wajib.
     */
    public function requiresKeterangan(): bool
    {
        return true; // Semua penyesuaian butah keterangan
    }
}
