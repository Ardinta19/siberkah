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
$fixtureDir = public_path('storage/ci-gallery');
if (!is_dir($fixtureDir)) { mkdir($fixtureDir, 0755, true); }
foreach (['berkah-kreatif','katalog-editorial'] as $order => $name) {
    copy(public_path("images/$name.webp"), "$fixtureDir/$name.webp");
    DB::table('gambar_produk')->insert(['produk_id'=>$product,'jalur_berkas'=>"ci-gallery/$name.webp",'urutan'=>$order+1,'teks_alternatif'=>"Foto uji ".($order+1)]);
}
DB::table('varian_produk')->insert(['produk_id' => $product, 'nama' => 'Putih', 'harga' => 130000, 'stok' => 5]);
DB::table('zona_antar')->insert(['nama' => 'Zona Uji', 'jarak_maks_km' => 5, 'ongkir' => 10000]);
DB::table('rekening_pembayaran')->insert(['nama_bank' => 'BRI', 'nomor_rekening' => '00000000', 'atas_nama' => 'BUKAN REKENING NYATA']);
$password = bin2hex(random_bytes(24));
foreach (['owner','admin','staf_kurir'] as $role) {
    DB::table('pengguna')->insert(['nama' => 'Operator CI', 'surel' => $role.'@example.test', 'peran' => $role, 'aktif' => true,
        'wajib_ganti_sandi' => false, 'kata_sandi' => password_hash($password, PASSWORD_BCRYPT)]);
}
$session = app('session.store');
$session->start();
$session->put(auth('customer')->getName(), $customer);
$session->save();
$name = config('session.cookie');
$enc = app('encrypter');
echo json_encode(['password' => $password, 'cookie' => ['name' => $name, 'value' => $enc->encrypt(CookieValuePrefix::create($name, $enc->getKey()).$session->getId(), false)]]);
