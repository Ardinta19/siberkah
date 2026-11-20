<?php
// CLI only: synthetic post-OTP session, never an HTTP route or a real customer.
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli' || getenv('GITHUB_ACTIONS') !== 'true' || getenv('DB_DATABASE') !== 'siberkah_checkout_ci' || getenv('APP_ENV') !== 'testing') { exit(2); }
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$customer = DB::table('pelanggan')->insertGetId(['nomor_wa' => '628111111199', 'nama' => 'Browser CI', 'aktif' => true, 'nomor_terverifikasi_pada' => now(), 'versi_kebijakan' => config('checkout.privacy_version'), 'disetujui_pada' => now()]);
$category = DB::table('kategori')->insertGetId(['nama' => 'Buket CI', 'slug' => 'buket-ci']);
$product = DB::table('produk')->insertGetId(['nama' => 'Buket Browser', 'slug' => 'buket-browser', 'jenis' => 'jual', 'kategori_id' => $category]);
DB::table('varian_produk')->insert(['produk_id' => $product, 'nama' => 'Putih', 'harga' => 130000, 'stok' => 5]);
DB::table('zona_antar')->insert(['nama' => 'Zona Uji', 'jarak_maks_km' => 5, 'ongkir' => 10000]);
DB::table('rekening_pembayaran')->insert(['nama_bank' => 'BRI', 'nomor_rekening' => '00000000', 'atas_nama' => 'BUKAN REKENING NYATA']);
$session = app('session.store');
$session->start();
$session->put(auth('customer')->getName(), $customer);
$session->save();
$name = config('session.cookie');
$enc = app('encrypter');
echo json_encode(['name' => $name, 'value' => $enc->encrypt(CookieValuePrefix::create($name, $enc->getKey()).$session->getId(), false)]);
