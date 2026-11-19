<?php

namespace App\Enums;

/**
 * Tipe opsi produk sesuai SRS: pilihan, teks, satuan.
 * Menentukan bagaimana opsi ditampilkan dan divalidasi.
 */
enum OptionType: string
{
    case PILIHAN = 'pilihan';
    case TEKS = 'teks';
    case SATUAN = 'satuan';

    /**
     * Apakah opsi ini butuh nilai dari nilai_opsi (dropdown/radio).
     */
    public function requiresOptionValue(): bool
    {
        return $this === self::PILIHAN;
    }

    /**
     * Apakah opsi ini butuh input teks bebas dari pelanggan.
     */
    public function requiresTextInput(): bool
    {
        return $this === self::TEKS;
    }

    /**
     * Apakah opsi ini butuh input jumlah (per satuan).
     */
    public function requiresQuantityInput(): bool
    {
        return $this === self::SATUAN;
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::PILIHAN => 'Pilihan',
            self::TEKS => 'Teks Bebas',
            self::SATUAN => 'Per Satuan',
        };
    }

    /**
     * Input type HTML yang sesuai.
     */
    public function htmlInputType(): string
    {
        return match ($this) {
            self::PILIHAN => 'radio', // atau select
            self::TEKS => 'text',
            self::SATUAN => 'number',
        };
    }

    /**
     * Validasi tambahan untuk tipe ini.
     */
    public function validationRules(): array
    {
        return match ($this) {
            self::PILIHAN => ['required', 'exists:nilai_opsi,id'],
            self::TEKS => ['required', 'string', 'max:500'],
            self::SATUAN => ['required', 'integer', 'min:1'],
        };
    }
}
