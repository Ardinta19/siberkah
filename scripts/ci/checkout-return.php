<?php
// Synthetic start state for Chrome only: the return inspection already happened.
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (PHP_SAPI !== 'cli' || getenv('GITHUB_ACTIONS') !== 'true' || getenv('DB_DATABASE') !== 'siberkah_checkout_ci' || getenv('APP_ENV') !== 'testing') { exit(2); }
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
abort_unless(DB::connection()->getDatabaseName() === 'siberkah_checkout_ci',403);
echo DB::transaction(function () {
    $customer = DB::table('pelanggan')->where('nomor_wa','628111111199')->firstOrFail();
    $unit = DB::table('unit_sewa')->where('kode','CI-R01')->firstOrFail();
    $variant = DB::table('varian_produk')->where('id',$unit->varian_produk_id)->firstOrFail();
    $order = DB::table('pesanan')->insertGetId(['nomor'=>'CI-RETUR-01','pelanggan_id'=>$customer->id,'status'=>'sudah_dikembalikan','asal'=>'situs','nama_pemesan'=>'Retur CI','nomor_wa_pemesan'=>$customer->nomor_wa,'cara_serah'=>'antar','tanggal_serah'=>today(),'jenis_jaminan'=>'deposit','nominal_jaminan'=>100000,'subtotal'=>50000,'total'=>50000,'jumlah_dibayar'=>50000]);
    DB::table('checkout_sewa')->insert(['pesanan_id'=>$order,'pelanggan_id'=>$customer->id,'kunci'=>(string)Str::uuid(),'sidik'=>hash('sha256','ci-retur'),'versi_privasi'=>config('checkout.privacy_version'),'disetujui_pada'=>now()]);
    $item = DB::table('item_pesanan')->insertGetId(['pesanan_id'=>$order,'produk_id'=>$variant->produk_id,'varian_produk_id'=>$variant->id,'unit_sewa_id'=>$unit->id,'nama_produk'=>'Papan CI','nama_varian'=>'Kayu','harga_satuan'=>50000,'subtotal'=>50000]);
    foreach (['serah'=>'baik','kembali'=>'rusak'] as $stage=>$condition) {
        DB::table('inspeksi_sewa')->insert(['item_pesanan_id'=>$item,'tahap'=>$stage,'kondisi'=>$condition,'daftar_periksa'=>json_encode(['permukaan'=>$condition]),'pengguna_id'=>DB::table('pengguna')->where('surel','staf_kurir@example.test')->value('id'),'dilakukan_pada'=>now()]);
    }
    // Separate free unit for the Admin same-day booking scenario.
    DB::table('produk')->where('id',$variant->produk_id)->update(['aktif'=>true,'izin_hari_sama'=>true]);
    DB::table('unit_sewa')->insert(['varian_produk_id'=>$variant->id,'kode'=>'CI-MANUAL','status'=>'aktif']);
    // Confirmed board with a separate unit, ready for the production-role scenario.
    $day = substr(trim(file_get_contents(getenv('CHECKOUT_CI_CLOCK'))),0,10);
    $board = DB::table('pesanan')->insertGetId(['nomor'=>'CI-PRODUKSI','pelanggan_id'=>$customer->id,'status'=>'dikonfirmasi','asal'=>'situs','nama_pemesan'=>'Produksi CI','nomor_wa_pemesan'=>$customer->nomor_wa,'cara_serah'=>'antar','alamat_antar'=>'Alamat papan CI','tanggal_serah'=>$day,'subtotal'=>100000,'total'=>100000,'jumlah_dibayar'=>0,'saldo'=>100000,'tunai_saat_serah'=>true]);
    DB::table('checkout_sewa')->insert(['pesanan_id'=>$board,'denda_per_jam'=>10000,'pelanggan_id'=>$customer->id,'kunci'=>(string)Str::uuid(),'sidik'=>hash('sha256','ci-produksi'),'versi_privasi'=>config('checkout.privacy_version'),'disetujui_pada'=>now()]);
    DB::table('desain_papan')->insert(['pesanan_id'=>$board,'metode'=>'spidol','jadwal_serah'=>$day.' 14:00:00']);
    foreach (['CI-PROD','CI-PROD-B'] as $code) {
        $boardUnit = DB::table('unit_sewa')->insertGetId(['varian_produk_id'=>$variant->id,'kode'=>$code,'status'=>'aktif']);
        $line = DB::table('item_pesanan')->insertGetId(['pesanan_id'=>$board,'produk_id'=>$variant->produk_id,'varian_produk_id'=>$variant->id,'unit_sewa_id'=>$boardUnit,'nama_produk'=>'Papan produksi CI','nama_varian'=>'Kayu','harga_satuan'=>50000,'subtotal'=>50000]);
        DB::table('hari_reservasi')->insert(['pesanan_id'=>$board,'item_pesanan_id'=>$line,'unit_sewa_id'=>$boardUnit,'tanggal'=>$day,'dibuat_pada'=>now()]);
    }
    return $order;
});
