<?php

namespace App\Enums;

/**
 * Cara serah barang sesuai SRS BR-17.
 * Papan sewa hanya diantar, buket bisa diambil atau diantar.
 */
enum DeliveryType: string
{
    case ANTAR = 'antar';
    case AMBIL = 'ambil';

    /**
     * Apakah butuh alamat & zona ongkir.
     */
    public function requiresAddress(): bool
    {
        return $this === self::ANTAR;
    }

    /**
     * Apakah butuh jam antar.
     */
    public function requiresTimeSlot(): bool
    {
        return true; // Keduanya butuh jam
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::ANTAR => 'Diantar',
            self::AMBIL => 'Ambil di Toko',
        };
    }
}
