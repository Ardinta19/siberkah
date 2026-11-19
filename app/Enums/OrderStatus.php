<?php

namespace App\Enums;

/**
 * Status pesanan mengikuti transisi T01-T14 dari SRS.
 * Setiap perpindahan status harus lewat TransitionOrderStatus action.
 */
enum OrderStatus: string
{
    case MENUNGGU_PEMBAYARAN = 'menunggu_pembayaran';
    case KEDALUWARSA = 'kedaluwarsa';
    case DIKONFIRMASI = 'dikonfirmasi';
    case SEDANG_DIPRODUKSI = 'sedang_diproduksi';
    case SIAP = 'siap';
    case SUDAH_DISERAHKAAN = 'sudah_diserahkan';
    case SUDAH_DIKEMBALIKAN = 'sudah_dikembalikan';
    case SELESAI = 'selesai';
    case DIBATALKAN = 'dibatalkan';

    /**
     * Status yang dianggap "aktif" (belum selesai/batal/expired).
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::MENUNGGU_PEMBAYARAN,
            self::DIKONFIRMASI,
            self::SEDANG_DIPRODUKSI,
            self::SIAP,
            self::SUDAH_DISERAHKAAN,
            self::SUDAH_DIKEMBALIKAN => true,
            self::KEDALUWARSA,
            self::SELESAI,
            self::DIBATALKAN => false,
        };
    }

    /**
     * Status yang bisa dibatalkan oleh pelanggan (hanya MENUNGGU_PEMBAYARAN).
     */
    public function canBeCancelledByCustomer(): bool
    {
        return $this === self::MENUNGGU_PEMBAYARAN;
    }

    /**
     * Status yang butuh saldo nol sebelum bisa diserahkan (BR-26).
     */
    public function requiresZeroBalanceBeforeHandover(): bool
    {
        return $this === self::SIAP;
    }

    /**
     * Status final (tidak bisa berubah lagi).
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::SELESAI, self::DIBATALKAN => true,
            default => false,
        };
    }

    /**
     * Label untuk tampilan pelanggan (Bahasa Indonesia, sapaan "Kak").
     */
    public function customerLabel(): string
    {
        return match ($this) {
            self::MENUNGGU_PEMBAYARAN => 'Pesanan Kakak sudah tercatat. Selesaikan pembayaran sebelum batas waktu supaya tanggalnya tetap aman.',
            self::KEDALUWARSA => 'Batas waktu pembayaran sudah lewat dan tanggal dilepas. Kalau sudah terlanjur transfer, hubungi kami ya, Kak.',
            self::DIKONFIRMASI => 'Pembayaran sudah kami terima. Pesanan Kakak sudah masuk jadwal.',
            self::SEDANG_DIPRODUKSI => 'Pesanan sedang kami kerjakan. Teks sudah tidak bisa diubah.',
            self::SIAP => 'Pesanan sudah siap. Kami atur antar atau ambil sesuai pilihan Kakak.',
            self::SUDAH_DISERAHKAAN => 'Pesanan sudah ada di tangan Kakak. Untuk barang sewa, mohon dikembalikan paling lambat pukul 18.00.',
            self::SUDAH_DIKEMBALIKAN => 'Barang sewa sudah kami terima kembali dan sedang diperiksa.',
            self::SELESAI => 'Pesanan selesai. Terima kasih sudah memilih Berkah Kreatif.',
            self::DIBATALKAN => 'Pesanan dibatalkan. Keterangan pengembalian dana ada di bawah bila berlaku.',
        };
    }

    /**
     * Valid transisi ke status berikutnya (berdasarkan tabel T01-T14).
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::MENUNGGU_PEMBAYARAN => [self::DIKONFIRMASI, self::KEDALUWARSA, self::DIBATALKAN],
            self::KEDALUWARSA => [self::DIKONFIRMASI, self::KEDALUWARSA], // T13/T14
            self::DIKONFIRMASI => [self::SEDANG_DIPRODUKSI, self::SIAP, self::DIBATALKAN], // T12
            self::SEDANG_DIPRODUKSI => [self::SIAP, self::DIBATALKAN], // T12
            self::SIAP => [self::SUDAH_DISERAHKAAN, self::DIBATALKAN], // T12
            self::SUDAH_DISERAHKAAN => [self::SUDAH_DIKEMBALIKAN, self::SELESAI], // T09/T10
            self::SUDAH_DIKEMBALIKAN => [self::SELESAI],
            self::SELESAI => [],
            self::DIBATALKAN => [],
        };
    }

    /**
     * Cek apakah bisa transisi ke status target.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->validTransitions(), true);
    }

    /**
     * Kode transisi untuk status ini (T01-T14).
     */
    public function transitionCode(): ?string
    {
        return match ($this) {
            self::MENUNGGU_PEMBAYARAN => 'T01',
            self::KEDALUWARSA => 'T03',
            self::DIKONFIRMASI => 'T02',
            self::SEDANG_DIPRODUKSI => 'T05',
            self::SIAP => 'T06', // atau T07 dari SEDANG_DIPRODUKSI
            self::SUDAH_DISERAHKAAN => 'T08',
            self::SUDAH_DIKEMBALIKAN => 'T09',
            self::SELESAI => 'T10', // atau T11 dari SUDAH_DIKEMBALIKAN
            self::DIBATALKAN => 'T12', // atau T04 dari MENUNGGU_PEMBAYARAN
        };
    }
}
