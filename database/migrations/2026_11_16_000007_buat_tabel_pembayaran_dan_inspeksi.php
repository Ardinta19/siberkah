<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // jenis: dp, pelunasan, penuh, refund.
        // status: menunggu, terverifikasi, ditolak, kedaluwarsa.
        // nominal_tertunda hanya terisi selama status menunggu, sehingga dua
        // pembayaran yang sedang menunggu tidak bisa punya nominal yang sama.
        // Belum ada kolom bukti transfer: ditambah hanya jika BYR-05 berarti unggah.
        Schema::create('pembayaran', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('pesanan_id')->constrained('pesanan')->restrictOnDelete();
            $tabel->foreignId('rekening_pembayaran_id')->nullable()->constrained('rekening_pembayaran')->restrictOnDelete();
            $tabel->string('jenis', 12);
            $tabel->string('status', 15)->default('menunggu');
            $tabel->unsignedInteger('tagihan');
            $tabel->unsignedSmallInteger('kode_unik')->nullable();
            $tabel->unsignedInteger('nominal_diharapkan');
            $tabel->unsignedInteger('nominal_tertunda')->nullable();
            $tabel->unsignedInteger('nominal_diterima')->nullable();
            $tabel->dateTime('dikonfirmasi_pelanggan_pada')->nullable();
            $tabel->dateTime('diterima_pada')->nullable();
            $tabel->foreignId('pengguna_id')->nullable()->constrained('pengguna')->restrictOnDelete();
            $tabel->dateTime('diverifikasi_pada')->nullable();
            $tabel->string('catatan_verifikasi', 300)->nullable();
            $tabel->catatWaktu();

            $tabel->unique('nominal_tertunda', 'uniq_nominal_tertunda');
            $tabel->index(['status', 'dibuat_pada'], 'idx_pembayaran_status_waktu');
        });

        // tahap: serah atau kembali. kondisi: baik, cacat_ringan, rusak.
        // dilakukan_pada memakai dateTime (bukan timestamp), alasannya sama dengan OTP.
        Schema::create('inspeksi_sewa', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('item_pesanan_id')->constrained('item_pesanan')->restrictOnDelete();
            $tabel->string('tahap', 10);
            $tabel->string('kondisi', 20);
            $tabel->text('catatan')->nullable();
            $tabel->foreignId('pengguna_id')->constrained('pengguna')->restrictOnDelete();
            $tabel->dateTime('dilakukan_pada');
            $tabel->timestamp('dibuat_pada')->nullable();

            $tabel->unique(['item_pesanan_id', 'tahap'], 'uniq_inspeksi_tahap');
        });

        // Enam foto per tahap, urutan 1 sampai 6.
        Schema::create('foto_inspeksi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('inspeksi_sewa_id')->constrained('inspeksi_sewa')->cascadeOnDelete();
            $tabel->unsignedTinyInteger('urutan');
            $tabel->string('jalur_berkas');
            $tabel->timestamp('dibuat_pada')->nullable();

            $tabel->unique(['inspeksi_sewa_id', 'urutan'], 'uniq_foto_urutan');
        });
    }

    public function down(): void
    {
        foreach (['foto_inspeksi', 'inspeksi_sewa', 'pembayaran'] as $nama) {
            Schema::dropIfExists($nama);
        }
    }
};