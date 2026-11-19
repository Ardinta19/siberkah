<?php

namespace App\Enums;

/**
 * Peran pengguna sesuai SRS matriks akses (3.11).
 * Dua guard Laravel: customer (OTP) dan staff (email/password).
 */
enum UserRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case STAF_PRODUKSI = 'staf_produksi';
    case STAF_KURIR = 'staf_kurir';

    /**
     * Apakah peran ini termasuk staf (guard: staff).
     */
    public function isStaff(): bool
    {
        return $this !== self::OWNER && $this !== self::ADMIN;
    }

    /**
     * Cek apakah peran ini sama dengan nilai yang diberikan.
     */
    public function is(string|UserRole $role): bool
    {
        return $this === ($role instanceof self ? $role : self::tryFrom($role));
    }

    /**
     * Apakah peran ini bisa akses panel admin/owner.
     */
    public function canAccessAdminPanel(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa kelola katalog & pengaturan.
     */
    public function canManageCatalog(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Apakah peran ini bisa kelola unit sewa.
     */
    public function canManageRentalUnits(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa kelola pengaturan bisnis.
     */
    public function canManageSettings(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Apakah peran ini bisa lihat rekap keuangan.
     */
    public function canViewReports(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Apakah peran ini bisa lihat log aktivitas.
     */
    public function canViewActivityLogs(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Apakah peran ini bisa input pesanan manual.
     */
    public function canInputManualOrder(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa transisi status pesanan (sesuai T01-T14).
     */
    public function canTransitionStatus(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa start production (T05).
     */
    public function canStartProduction(): bool
    {
        return $this === self::STAF_PRODUKSI || $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa mark ready (T06/T07).
     */
    public function canMarkReady(): bool
    {
        return $this === self::STAF_PRODUKSI || $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa handover (T08).
     */
    public function canHandover(): bool
    {
        return $this === self::STAF_KURIR || $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa receive return (T09).
     */
    public function canReceiveReturn(): bool
    {
        return $this === self::STAF_KURIR || $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa complete order (T10/T11).
     */
    public function canCompleteOrder(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa cancel order (T04/T12).
     */
    public function canCancelOrder(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa verifikasi pembayaran.
     */
    public function canVerifyPayment(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa catat pembayaran.
     */
    public function canRecordPayment(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa atur blokir tanggal/kapasitas.
     */
    public function canManageCalendarBlocks(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    /**
     * Apakah peran ini bisa lihat agenda harian.
     */
    public function canViewAgenda(): bool
    {
        return true; // Semua staf + owner + admin
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin CS & Keuangan',
            self::STAF_PRODUKSI => 'Staf Produksi',
            self::STAF_KURIR => 'Staf Kurir',
        };
    }

    /**
     * Guard Laravel yang dipakai.
     */
    public function guard(): string
    {
        return $this === self::OWNER || $this === self::ADMIN || $this->isStaff()
            ? 'staff'
            : 'customer'; // Seharusnya tidak dipakai untuk pelanggan
    }

    /**
     * Semua peran staf (untuk middleware).
     */
    public static function staffRoles(): array
    {
        return [self::OWNER, self::ADMIN, self::STAF_PRODUKSI, self::STAF_KURIR];
    }

    /**
     * Semua peran yang bisa akses panel staf.
     */
    public static function panelRoles(): array
    {
        return [self::OWNER, self::ADMIN, self::STAF_PRODUKSI, self::STAF_KURIR];
    }
}
