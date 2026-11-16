<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Akun staf: owner, admin, staf. Tidak pernah dihapus, hanya dinonaktifkan.
        Schema::create('pengguna', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama', 100);
            $tabel->string('surel', 150)->unique();
            $tabel->string('kata_sandi');
            $tabel->string('peran', 20);
            $tabel->boolean('aktif')->default(true);
            $tabel->dateTime('masuk_terakhir_pada')->nullable();
            $tabel->rememberToken();
            $tabel->catatWaktu();
        });

        // Akun pelanggan: masuk dengan OTP. Nomor disimpan 628xx.
        Schema::create('pelanggan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nomor_wa', 15)->unique();
            $tabel->string('nama', 100)->nullable();
            $tabel->boolean('aktif')->default(true);
            $tabel->dateTime('nomor_terverifikasi_pada')->nullable();
            $tabel->dateTime('masuk_terakhir_pada')->nullable();
            $tabel->dateTime('dihapus_pada')->nullable();
            $tabel->catatWaktu();
        });

        // Kode OTP. Kolom waktu memakai dateTime, bukan timestamp,
        // supaya MariaDB tidak memperpanjang masa berlaku diam-diam.
        Schema::create('kode_otp', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nomor_wa', 15);
            $tabel->string('kode_hash');
            $tabel->string('alamat_ip', 45);
            $tabel->dateTime('kedaluwarsa_pada');
            $tabel->unsignedTinyInteger('percobaan')->default(0);
            $tabel->dateTime('dipakai_pada')->nullable();
            $tabel->string('status_kirim', 15)->default('menunggu');
            $tabel->timestamp('dibuat_pada')->nullable();

            $tabel->index(['nomor_wa', 'dibuat_pada'], 'idx_otp_nomor_waktu');
            $tabel->index(['alamat_ip', 'dibuat_pada'], 'idx_otp_ip_waktu');
        });
    }

    public function down(): void
    {
        foreach (['kode_otp', 'pelanggan', 'pengguna'] as $nama) {
            Schema::dropIfExists($nama);
        }
    }
};