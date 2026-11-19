<?php

namespace App\Enums;

/**
 * Jenis pembayaran sesuai SRS BR-04, BYR-01, BYR-06, BYR-08, BYR-09.
 */
enum PaymentType: string
{
    case DP = 'dp';
    case PELUNASAN = 'pelunasan';
    case PENUH = 'penuh';
    case REFUND = 'refund';

    /**
     * Jenis pembayaran yang memerlukan kode unik (semua kecuali refund).
     */
    public function requiresUniqueCode(): bool
    {
        return $this !== self::REFUND;
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::DP => 'DP 50%',
            self::PELUNASAN => 'Pelunasan',
            self::PENUH => 'Lunas',
            self::REFUND => 'Refund',
        };
    }

    /**
     * Jenis pembayaran yang bisa dicatat sebagai tunai (BR-08).
     */
    public function canBeCash(): bool
    {
        return $this === self::PENUH || $this === self::REFUND;
    }
}
