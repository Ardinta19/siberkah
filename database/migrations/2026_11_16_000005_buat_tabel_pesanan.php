<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penghitung nomor pesanan per bulan. Baris dikunci saat nomor diambil.
        Schema::create('urutan_nomor_pesanan', function (Blueprint $tabel) {
            $tabel->string('periode', 4)->primary();
            $tabel->unsignedInteger('terakhir')->default(0);
        });

        Schema::create('pesanan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nomor', 14)->unique();
            $tabel->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $tabel->string('status', 30)->default('menunggu_pembayaran');

            // asal: situs atau admin. pengguna_id terisi bila admin menginput manual.
            $tabel->string('asal', 10)->default('situs');
            $tabel->foreignId('pengguna_id')->nullable()->constrained('pengguna')->restrictOnDelete();

            $tabel->string('nama_pemesan', 100);
            $tabel->string('nomor_wa_pemesan', 15);
            $tabel->string('cara_serah', 10);
            $tabel->text('alamat_antar')->nullable();
            $tabel->foreignId('zona_antar_id')->nullable()->constrained('zona_antar')->restrictOnDelete();
            $tabel->date('tanggal_serah');
            $tabel->time('jam_serah_mulai')->nullable();
            $tabel->time('jam_serah_selesai')->nullable();
            $tabel->string('catatan_pelanggan', 500)->nullable();

            // Uang dalam rupiah bulat.
            $tabel->unsignedInteger('subtotal')->default(0);
            $tabel->unsignedInteger('ongkir')->default(0);
            $tabel->integer('penyesuaian')->default(0);
            $tabel->unsignedInteger('total')->default(0);
            $tabel->unsignedInteger('jumlah_dibayar')->default(0);
            $tabel->integer('saldo')->default(0);
            $tabel->boolean('wajib_dp')->default(false);

            $tabel->dateTime('tahan_hingga_pada')->nullable();
            $tabel->dateTime('batas_pelunasan_pada')->nullable();

            // Jaminan diserahkan langsung saat serah terima, tidak diunggah (BR-12).
            $tabel->string('jenis_jaminan', 20)->nullable();
            $tabel->unsignedInteger('nominal_jaminan')->nullable();
            $tabel->dateTime('jaminan_kembali_pada')->nullable();

            $tabel->unsignedTinyInteger('persen_refund')->nullable();
            $tabel->unsignedInteger('nominal_refund')->nullable();
            $tabel->string('alasan_batal', 300)->nullable();

            $tabel->dateTime('dikonfirmasi_pada')->nullable();
            $tabel->dateTime('diserahkan_pada')->nullable();
            $tabel->dateTime('dikembalikan_pada')->nullable();
            $tabel->dateTime('selesai_pada')->nullable();
            $tabel->dateTime('dibatalkan_pada')->nullable();
            $tabel->catatWaktu();

            $tabel->index(['status', 'tanggal_serah'], 'idx_pesanan_status_tanggal');
            $tabel->index(['pelanggan_id', 'dibuat_pada'], 'idx_pesanan_pelanggan_waktu');
        });

        // Sewa: satu baris per unit (jumlah = 1). Nama dan harga disalin
        // ke baris ini supaya riwayat tidak berubah saat katalog berubah.
        Schema::create('item_pesanan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('pesanan_id')->constrained('pesanan')->restrictOnDelete();
            $tabel->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $tabel->foreignId('varian_produk_id')->nullable()->constrained('varian_produk')->restrictOnDelete();
            $tabel->foreignId('unit_sewa_id')->nullable()->constrained('unit_sewa')->restrictOnDelete();
            $tabel->string('nama_produk', 150);
            $tabel->string('nama_varian', 80)->nullable();
            $tabel->unsignedInteger('harga_satuan');
            $tabel->unsignedSmallInteger('jumlah')->default(1);
            $tabel->unsignedInteger('subtotal');
            $tabel->date('tanggal_mulai')->nullable();
            $tabel->date('tanggal_selesai')->nullable();
            $tabel->string('tulisan_kustom', 300)->nullable();
            $tabel->catatWaktu();
        });

        Schema::create('opsi_item_pesanan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('item_pesanan_id')->constrained('item_pesanan')->cascadeOnDelete();
            $tabel->foreignId('opsi_produk_id')->nullable()->constrained('opsi_produk')->nullOnDelete();
            $tabel->foreignId('nilai_opsi_id')->nullable()->constrained('nilai_opsi')->nullOnDelete();
            $tabel->string('nama_opsi', 80);
            $tabel->string('label_nilai', 80);
            $tabel->unsignedInteger('harga_tambahan')->default(0);
            $tabel->string('teks_bebas', 300)->nullable();
        });

        // jenis: denda_telat, kerusakan, perpanjangan, diskon_manual, biaya_admin_refund.
        // Nominal boleh negatif (potongan). Hanya owner yang mengubah nominal.
        Schema::create('penyesuaian_pesanan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('pesanan_id')->constrained('pesanan')->restrictOnDelete();
            $tabel->string('jenis', 25);
            $tabel->string('keterangan', 200);
            $tabel->integer('nominal');
            $tabel->foreignId('pengguna_id')->constrained('pengguna')->restrictOnDelete();
            $tabel->timestamp('dibuat_pada')->nullable();
        });

        // pelaku: pelanggan, staf, atau sistem. kode_transisi: T01 sampai T14.
        Schema::create('riwayat_status_pesanan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('pesanan_id')->constrained('pesanan')->restrictOnDelete();
            $tabel->string('status_lama', 30)->nullable();
            $tabel->string('status_baru', 30);
            $tabel->string('kode_transisi', 4)->nullable();
            $tabel->string('pelaku', 10);
            $tabel->foreignId('pengguna_id')->nullable()->constrained('pengguna')->restrictOnDelete();
            $tabel->string('catatan', 300)->nullable();
            $tabel->timestamp('dibuat_pada')->nullable();

            $tabel->index(['pesanan_id', 'dibuat_pada'], 'idx_riwayat_pesanan_waktu');
        });
    }

    public function down(): void
    {
        foreach (['riwayat_status_pesanan', 'penyesuaian_pesanan', 'opsi_item_pesanan', 'item_pesanan', 'pesanan', 'urutan_nomor_pesanan'] as $nama) {
            Schema::dropIfExists($nama);
        }
    }
};