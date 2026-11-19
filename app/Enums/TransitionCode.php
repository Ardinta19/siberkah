<?php

namespace App\Enums;

/**
 * Kode transisi status T01-T14 mengikuti tabel transisi SRS Bab 3.9.
 * Setiap perpindahan status harus lewat TransitionOrderStatus action yang
 * cek kode transisi ini dan menulis ke riwayat_status_pesanan.
 */
enum TransitionCode: string
{
    case T01 = 'T01';
    case T02 = 'T02';
    case T03 = 'T03';
    case T04 = 'T04';
    case T05 = 'T05';
    case T06 = 'T06';
    case T07 = 'T07';
    case T07b = 'T07b'; // dari SIAP ke status lain
    case T07c = 'T07c'; // dari cancelled ke review
    case T08 = 'T08';
    case T09 = 'T09';
    case T10 = 'T10';
    case T11 = 'T11';
    case T12 = 'T12';
    case T13 = 'T13';
    case T14 = 'T14';

    /**
     * Valid transisi ke status berikutnya dari status saat ini.
     */
    public function validTransitionsFrom(self $current): array
    {
        return match ($current) {
            self::MENUNGGU_PEMBAYARAN => [self::DIKONFIRMASI, self::KEDALUWARSA, self::DIBATALKAN],
            self::DIKONFIRMASI => [self::SEDANG_DIPRODUKSI, self::SIAP, self::DIBATALKAN],
            self::SEDANG_DIPRODUKSI => [self::SIAP, self::DIBATALKAN],
            self::SIAP => [self::SUDAH_DISERAHKAAN, self::DIBATALKAN],
            self::SUDAH_DISERAHKAAN => [self::SUDAH_DIKEMBALIKAN, self::SELESAI],
            self::SUDAH_DIKEMBALIKAN => [self::SELESAI],
            self::SELESAI => [],
            self::DIBATALKAN => [],
        };
    }

    /**
     * Semua kode transisi dalam array.
     */
    public static function allCodes(): array
    {
        return [
            self::T01, self::T02, self::T03, self::T04,
            self::T05, self::T06, self::T07, self::T07b,
            self::T07c, self::T08, self::T09, self::T10,
            self::T11, self::T12, self::T13, self::T14,
        ];
    }

    /**
     * Apakah kode transisi ini adalah pembatalan.
     */
    public function isCancellation(): bool
    {
        return $this === self::T04 || $this === self::T12 || $this === self::T14;
    }

    /**
     * Apakah kode transisi ini adalah kadaluwarsa (penjadwal).
     */
    public function isAutoByScheduler(): bool
    {
        return $this === self::T03;
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::T01 => 'Baru -> Menunggu Pembayaran',
            self::T02 => 'Menunggu Pembayaran -> Dikonfirmasi',
            self::T03 => 'Penjadwal: Menunggu Pembayara -> Kedaluwarsa',
            self::T04 => 'Admin/Pelanggan: Dibatalkan',
            self::T05 => 'Dikonfirmasi -> Sedang Diproduksi',
            self::T06 => 'Dikonfirmasi -> Siap',
            self::T07 => 'Diproduksi -> Siap',
            self::T07b => 'Review dari Cancelled',
            self::T07c => 'Review from Cancelled flow',
            self::T08 => 'Siap -> Sudah Diserahkan',
            self::T09 => 'Diserahkan -> Sudah Dikembalikan',
            self::T10 => 'Sudah Diserahkan -> Selesai',
            self::T11 => 'Dikembalikan -> Selesai',
            self::T12 => 'Bisa dibatalkan',
            self::T13 => 'Kadaluwarsa -> Aktifkan Kembali',
            self::T14 => 'Kadaluwarsa -> Dana Dikembalikan',
        };
    }
}
