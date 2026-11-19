<?php

namespace App\Enums;

/**
 * Jenis produk sesuai SRS: sewa, jual, custom.
 * Menentukan perilaku ketersediaan, harga, dan alur pemesanan.
 */
enum ProductType: string
{
    case SEWA = 'sewa';
    case JUAL = 'jual';
    case CUSTOM = 'custom';

    /**
     * Apakah produk ini menggunakan unit sewa fisik (unit_sewa + hari_reservasi).
     */
    public function usesRentalUnits(): bool
    {
        return $this === self::SEWA;
    }

    /**
     * Apakah produk ini memiliki stok (stok siap jual).
     */
    public function hasStock(): bool
    {
        return $this === self::JUAL;
    }

    /**
     * Apakah produk ini dibuat setelah pesanan (custom).
     */
    public function isCustom(): bool
    {
        return $this === self::CUSTOM;
    }

    /**
     * Apakah produk ini butuh lead time (custom & sewa dengan stiker).
     */
    public function requiresLeadTime(): bool
    {
        return $this !== self::JUAL;
    }

    /**
     * Apakah produk ini bisa dipesan hari yang sama (BR-03).
     */
    public function allowsSameDayOrder(): bool
    {
        return $this === self::JUAL || $this === self::SEWA;
    }

    /**
     * Apakah produk ini butuh kapasitas produksi harian (BR-18).
     */
    public function requiresProductionCapacity(): bool
    {
        return $this === self::CUSTOM;
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::SEWA => 'Disewakan',
            self::JUAL => 'Dijual',
            self::CUSTOM => 'Custom (Dibuat Pesanan)',
        };
    }

    /**
     * Cara serah default (BR-17).
     */
    public function defaultDeliveryType(): DeliveryType
    {
        return match ($this) {
            self::SEWA => DeliveryType::ANTAR,
            self::JUAL => DeliveryType::AMBIL,
            self::CUSTOM => DeliveryType::AMBIL,
        };
    }

    /**
     * Apakah produk ini wajib lunas (tidak bisa DP) untuk total < 250k (BR-04).
     */
    public function forcesFullPaymentForSmallAmount(): bool
    {
        return true; // Semua jenis mengikuti aturan BR-04 yang sama
    }
}
