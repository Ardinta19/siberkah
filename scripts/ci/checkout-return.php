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
    return $order;
});
