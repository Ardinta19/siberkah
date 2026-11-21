<?php
// Read-only postconditions for the two-unit Chrome scenario. Never run on operator data.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

if (PHP_SAPI !== 'cli' || getenv('GITHUB_ACTIONS') !== 'true' || getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'siberkah_checkout_ci') { exit(2); }
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
function verify(bool $ok, string $label): void {
    if (!$ok) { throw new RuntimeException("Inspeksi CI: $label"); }
}
verify(DB::connection()->getDatabaseName() === 'siberkah_checkout_ci', 'database salah');
$stage = $argv[1] ?? '';
verify(in_array($stage,['serah','kembali'],true), 'tahap salah');
$returned = $stage === 'kembali';
$order = DB::table('pesanan')->where('nomor','CI-PRODUKSI')->firstOrFail();
$rows = fn(string $table) => DB::table($table)->where('pesanan_id',$order->id);
verify($order->status === ($returned ? 'sudah_dikembalikan' : 'sudah_diserahkan'), 'status pesanan');
verify($order->jenis_jaminan === 'deposit' && (int)$order->nominal_jaminan === 120000, 'deposit berubah');
verify((int)$order->jumlah_dibayar === 100000 && (int)$order->saldo === ($returned ? 20000 : 0), 'deposit tercampur saldo atau denda salah');
$cash = $rows('pembayaran')->sole();
verify($cash->jenis === 'penuh' && $cash->status === 'terverifikasi' && (bool)$cash->tunai_saat_serah, 'jenis atau status penerimaan tunai');
verify((int)$cash->nominal_diterima === 100000 && (int)$cash->tagihan === 100000, 'penerimaan tunai harus tepat tanpa deposit');
verify((int)$cash->pengguna_id === (int)$rows('riwayat_status_pesanan')->where('kode_transisi','T08')->sole()->pengguna_id, 'petugas penerima tunai berbeda dari petugas serah');
$adjust = $rows('penyesuaian_pesanan');
verify($adjust->count() === ($returned ? 1 : 0), 'jumlah penyesuaian');
if ($returned) { verify((int)$adjust->where('jenis','denda_telat')->value('nominal') === 20000,'denda dua unit'); }
foreach (['T08'=>1,'T09'=>(int)$returned] as $code=>$count) {
    verify($rows('riwayat_status_pesanan')->where('kode_transisi',$code)->count() === $count, "jumlah transisi $code");
}
foreach (['CI-PROD'=>false,'CI-PROD-B'=>true] as $code=>$damaged) {
    $unit = DB::table('unit_sewa')->where('kode',$code)->firstOrFail();
    verify($unit->status === ($returned && $damaged ? 'rusak' : 'aktif'), "status $code");
    verify($rows('penguasaan_unit')->where('unit_sewa_id',$unit->id)->count() === ($returned ? 0 : 1), "penguasaan $code");
    $item = $rows('item_pesanan')->where('unit_sewa_id',$unit->id)->firstOrFail();
    $checks = DB::table('inspeksi_sewa')->where('item_pesanan_id',$item->id);
    verify($checks->count() === ($returned ? 2 : 1), "jumlah inspeksi $code");
    foreach ($returned ? ['serah','kembali'] : ['serah'] as $step) {
        $record = (clone $checks)->where('tahap',$step)->firstOrFail();
        verify($record->kondisi === (!$damaged ? 'baik' : ($step === 'serah' ? 'cacat_ringan' : 'rusak')), "kondisi $code $step");
        $photos = DB::table('foto_inspeksi')->where('inspeksi_sewa_id',$record->id);
        verify($photos->count() === (int)$damaged, "jumlah bukti $code $step");
        if ($damaged) {
            $path = $photos->value('jalur_berkas');
            verify(Storage::disk('local')->exists($path) && !Storage::disk('public')->exists($path), "penyimpanan bukti $code $step");
        }
    }
}
echo "Inspeksi $stage: status, penguasaan, bukti, riwayat dan saldo cocok.\n";
