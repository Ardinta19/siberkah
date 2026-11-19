<?php

namespace App\Enums;

/**
 * Status pembayaran sesuai SRS BYR-01 sampai BYR-11.
 * Pembayaran diverifikasi manual oleh Admin/Owner.
 */
enum PaymentStatus: string
{
    case MENUNGGU = 'menunggu';
    case TERVERIFIKASI = 'terverifikasi';
    case DITOLAK = 'ditolak';
    case KEDALUWARSA = 'kedaluwarsa';

    /**
     * Status yang memungkinkan pembuatan tagihan baru (misal pelunasan DP).
     */
    public function allowsNewBill(): bool
    {
        return $this === self::DITOLAK || $this === self::KEDALUWARSA;
    }

    /**
     * Status yang sudah final (tidak bisa diubah).
     */
    public function isFinal(): bool
    {
        return $this !== self::MENUNGGU;
    }

    /**
     * Label untuk tampilan pelanggan.
     */
    public function label(): string
    {
        return match ($this) {
            self::MENUNGGU => 'Menunggu verifikasi',
            self::TERVERIFIKASI => 'Terverifikasi',
            self::DITOLAK => 'Ditolak',
            self::KEDALUWARSA => 'Kedaluwarsa',
        };
    }

    /**
     * Warna badge untuk UI.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::MENUNGGU => 'warning',
            self::TERVERIFIKASI => 'success',
            self::DITOLAK => 'danger',
            self::KEDALUWARSA => 'secondary',
        };
    }
}
