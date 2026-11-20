<?php

namespace App\Console\Commands;

use App\Support\BusinessSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only check for the specific cash-delivery acceptance scenario, not a repair tool. */
final class OperatorCheck extends Command
{
    protected $signature = 'uji:operator {--pesanan= : Nomor pesanan uji setelah serah dan setor}';
    protected $description = 'Periksa prasyarat atau hasil skenario DP dan kas antar tanpa mengubah data';

    public function handle(): int
    {
        $ci = app()->environment('testing') && getenv('GITHUB_ACTIONS') === 'true';
        $expected = $ci ? 'siberkah_checkout_ci' : 'siberkah_uji';
        if ((! $ci && ! app()->environment('local', 'staging')) || ! config('checkout.staging')) {
            $this->error('Berhenti: perlu lingkungan dan flag checkout uji. Tidak ada data yang diubah.');
            return self::FAILURE;
        }
        try {
            $db = DB::connection();
            if (! in_array($db->getDriverName(), ['mysql','mariadb'], true) || $db->getDatabaseName() !== $expected
                || $db->selectOne('SELECT DATABASE() AS nama', [], false)->nama !== $expected
                || $db->selectOne('SELECT DATABASE() AS nama')->nama !== $expected) {
                $this->error('Berhenti: basis data efektif bukan basis data uji yang diizinkan.');
                return self::FAILURE;
            }
            $required = array_map(fn ($path) => basename($path, '.php'), glob(database_path('migrations/*.php')));
            if (! Schema::hasTable('migrations') || array_diff($required, DB::table('migrations')->pluck('migration')->all())) {
                $this->error('Migrasi belum lengkap. Periksa migrate:status dan backup DB uji sebelum melanjutkan.');
                return self::FAILURE;
            }
            $number = $this->option('pesanan');
            if ($this->input->hasParameterOption('--pesanan') && (! is_string($number) || ! preg_match('/^BK-[0-9]{4}-[0-9]{4}$/D', $number))) {
                $this->error('Nomor pesanan harus berformat BK-YYMM-NNNN.');
                return self::FAILURE;
            }
            $checks = DB::transaction(fn () => $number ? $this->result($number) : $this->readiness());
            foreach ($checks as $label => $ok) {
                $this->line(($ok ? 'OK    ' : 'GAGAL ').$label);
            }
            $this->line('Pemeriksaan hanya baca. OTP nyata, merchant QRIS, uang fisik, scheduler dan backup tetap harus diuji terpisah.');
            return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable) {
            // SQL/connection exceptions may contain identifiers or credentials; do not print them.
            $this->error('Pemeriksaan gagal. Periksa koneksi, skema dan konfigurasi lokal; jangan bagikan .env.');
            return self::FAILURE;
        }
    }

    private function readiness(): array
    {
        $roles = DB::table('pengguna')->where('aktif', true)->distinct()->pluck('peran');
        $version = config('checkout.privacy_version');
        $items = DB::table('produk as p')->join('kategori as k', 'k.id', '=', 'p.kategori_id')->join('varian_produk as v', 'v.produk_id', '=', 'p.id')
            ->where(['p.aktif' => true, 'k.aktif' => true, 'v.aktif' => true, 'p.jenis' => 'jual', 'v.harga' => 130000])->where('v.stok', '>=', 2)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('opsi_produk as o')->whereColumn('o.produk_id', 'p.id')->where('o.wajib', true));
        return [
            'Versi kebijakan privasi checkout terisi dan sesuai batas panjang' => is_string($version) && filled($version) && mb_strlen($version) <= 40,
            'Akun aktif terpisah untuk Admin, Kurir dan Owner' => collect(['admin','staf_kurir','owner'])->diff($roles)->isEmpty(),
            'Buket siap jual Rp130.000, stok minimal dua, tanpa opsi wajib' => $items->exists(),
            'Zona antar aktif dengan ongkir Rp10.000 dalam radius layanan' => DB::table('zona_antar')->where(['aktif' => true,'ongkir' => 10000])->where('jarak_maks_km', '<=', 15)->exists(),
            'DP tersedia untuk total Rp270.000' => BusinessSettings::value('ambang_dp_rupiah') <= 270000,
            'Terdapat tujuan pembayaran aktif' => DB::table('rekening_pembayaran')->where('aktif', true)->exists(),
        ];
    }

    private function result(string $number): array
    {
        $order = DB::table('pesanan')->where('nomor', $number)->first();
        if (! $order) { return ['Pesanan uji ditemukan' => false]; }
        $bills = DB::table('pembayaran')->where('pesanan_id', $order->id)->get();
        $dp = $bills->firstWhere('jenis', 'dp');
        $cash = $bills->firstWhere('jenis', 'pelunasan');
        $mutations = DB::table('mutasi_masuk')->whereIn('pembayaran_id', $bills->pluck('id'))->get();
        $m = $mutations->first();
        $entries = DB::table('setoran_kas_antar')->whereIn('pembayaran_id', $bills->pluck('id'))->orderBy('id')->get();
        $balance = 0;
        $valid = $entries->isNotEmpty() && $cash !== null;
        foreach ($entries as $e) {
            $valid = $valid && $e->pembayaran_id == $cash->id && $e->kasir_id != $cash->pengguna_id && (int) $e->sebelum === $balance;
            $balance = (int) $e->sesudah;
        }
        return [
            'Pesanan situs: buket antar, DP dan pelunasan tunai saat serah' => $order->asal === 'situs' && $order->cara_serah === 'antar' && (bool) $order->wajib_dp && (bool) $order->tunai_saat_serah && DB::table('checkout_buket')->where('pesanan_id', $order->id)->exists(),
            'Sudah diserahkan, total dan dibayar Rp270.000, saldo nol' => in_array($order->status, ['sudah_diserahkan','selesai'], true) && $order->total == 270000 && $order->jumlah_dibayar == 270000 && $order->saldo == 0,
            'Tepat dua pembayaran terverifikasi: DP dan tunai masing-masing Rp135.000' => $bills->count() === 2 && $dp && $cash && ! $dp->tunai_saat_serah && $cash->tunai_saat_serah && $dp->status === 'terverifikasi' && $cash->status === 'terverifikasi' && $dp->tagihan == 135000 && $cash->tagihan == 135000 && $cash->nominal_diterima == 135000,
            'Satu mutasi DP sesuai nominal aktual termasuk kode unik; tidak ada mutasi untuk setoran' => $mutations->count() === 1 && $dp && $m->pembayaran_id == $dp->id && $m->hasil === 'terverifikasi' && $m->nominal == $dp->nominal_diharapkan && $m->nominal == $dp->nominal_diterima && $dp->kode_unik >= 1 && $dp->kode_unik <= 999 && $dp->nominal_diharapkan == 135000 + $dp->kode_unik,
            'Riwayat setoran konsisten; pemeriksa berbeda; total fisik tercatat Rp135.000' => $valid && $balance === 135000,
        ];
    }
}
