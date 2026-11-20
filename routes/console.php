<?php

use App\Actions\ReleaseOrderHold;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('pesanan:expire', function (ReleaseOrderHold $holds) {
    $failed = 0;
    $orders = Pesanan::where('status', 'menunggu_pembayaran')->where('tahan_hingga_pada', '<=', now());
    foreach ($orders->select('id')->lazyById(100) as $order) {
        try {
            $holds->expire($order->id);
        } catch (DomainException $exception) {
            report($exception);
            $this->error("Pesanan #{$order->id} perlu rekonsiliasi.");
            $failed++;
        }
    }
    return $failed ? 1 : 0;
})->purpose('Lepaskan penahanan pesanan belum dibayar yang telah berakhir');

Schedule::command('pesanan:expire')->everyMinute()->withoutOverlapping(10);

// Deliberately no delete option: old ledger rows can still have refund or reconciliation obligations.
Artisan::command('privasi:tinjau-retensi', function () {
    $years = \App\Support\BusinessSettings::value('retensi_transaksi_tahun');
    $cutoff = now('Asia/Jakarta')->startOfDay()->subYearsNoOverflow($years);
    $old = Pesanan::where(function ($q) use ($cutoff) {
        $q->where(fn ($p) => $p->where('status', 'selesai')->where('selesai_pada', '<', $cutoff))
            ->orWhere(fn ($p) => $p->where('status', 'dibatalkan')->where('dibatalkan_pada', '<', $cutoff));
    })->count();
    $this->info("Tinjauan saja: {$old} pesanan selesai/batal sebelum {$cutoff->toDateString()} ({$years} tahun). Tidak ada data yang dihapus.");
})->purpose('Hitung transaksi lama untuk tinjauan retensi tanpa menampilkan identitas atau menghapus data');

Artisan::command('reservations:audit', function () {
    // One consistent read; T09/T11 retain historical reservation rows, T03/T04/T12 must release them.
    $n = \Illuminate\Support\Facades\DB::selectOne("SELECT
        (SELECT COUNT(*) FROM hari_reservasi h JOIN pesanan p ON p.id=h.pesanan_id
         JOIN item_pesanan i ON i.id=h.item_pesanan_id
         WHERE p.status IN ('dibatalkan','kedaluwarsa') OR h.tanggal<>p.tanggal_serah OR i.pesanan_id<>p.id
         OR i.unit_sewa_id IS NULL OR i.unit_sewa_id<>h.unit_sewa_id) AS tidak_cocok,
        (SELECT COUNT(*) FROM checkout_sewa c JOIN pesanan p ON p.id=c.pesanan_id JOIN item_pesanan i ON i.pesanan_id=p.id
         WHERE p.status NOT IN ('dibatalkan','kedaluwarsa','selesai','sudah_dikembalikan') AND NOT EXISTS
         (SELECT 1 FROM hari_reservasi h WHERE h.pesanan_id=p.id AND h.item_pesanan_id=i.id AND h.unit_sewa_id=i.unit_sewa_id AND h.tanggal=p.tanggal_serah)) AS hilang");
    $this->line("Reservasi tidak cocok: {$n->tidak_cocok}; item sewa aktif tanpa reservasi: {$n->hilang}.");
    return ($n->tidak_cocok || $n->hilang) ? 1 : 0;
})->purpose('Audit reservasi sewa tanpa mengubah data atau menampilkan identitas');

Artisan::command('staf:buat {surel} {peran} {nama}', function () {
    $data = ['surel' => strtolower(trim($this->argument('surel'))), 'peran' => $this->argument('peran'), 'nama' => trim($this->argument('nama'))];
    $rules = ['surel' => 'required|email|max:150|unique:pengguna,surel', 'nama' => 'required|string|max:100',
        'peran' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\UserRole::class)]];
    if (validator($data, $rules)->fails() || ! $this->input->isInteractive()) {
        $this->error('Periksa surel, nama dan peran; gunakan terminal interaktif.');
        return 1;
    }
    $password = $this->secret('Kata sandi sementara (12–72 byte)', false);
    if (! is_string($password) || mb_strlen($password) < 12 || strlen($password) > 72 || $this->secret('Ulangi kata sandi sementara', false) !== $password) {
        $this->error('Kata sandi tidak memenuhi syarat atau tidak cocok.');
        return 1;
    }
    \Illuminate\Support\Facades\DB::transaction(function () use ($data, $password) {
        $staff = \App\Models\Pengguna::create($data + ['kata_sandi' => \Illuminate\Support\Facades\Hash::make($password), 'aktif' => true]);
        \App\Support\StaffAudit::record('buat_staf_cli','pengguna',$staff->id,null,['peran'=>$staff->peran,'aktif'=>true]);
    });
    $this->info('Akun dibuat. Ganti kata sandi saat masuk pertama.');
    return 0;
})->purpose('Buat akun staf atas permintaan Owner; sandi tidak diberikan lewat argumen');

Artisan::command('staf:nonaktif {surel}', function () {
    $email = strtolower(trim($this->argument('surel')));
    // Serialize decisions about the last active Owner, including competing CLI requests.
    $ok = \Illuminate\Support\Facades\DB::transaction(function () use ($email) {
        $owners = \App\Models\Pengguna::where('peran', 'owner')->where('aktif', true)->orderBy('id')->lockForUpdate()->get();
        $staff = \App\Models\Pengguna::where('surel', $email)->lockForUpdate()->first();
        if (! $staff || ($staff->aktif && $staff->isOwner() && $owners->count() <= 1)) {
            return false;
        }
        if ($staff->aktif) { \App\Support\StaffAudit::record('nonaktif_staf_cli','pengguna',$staff->id,['aktif'=>true,'peran'=>$staff->peran],['aktif'=>false,'peran'=>$staff->peran]); }
        $staff->forceFill(['aktif' => false, 'remember_token' => null])->save();
        return true;
    }, 3);
    $this->line($ok ? 'Akun nonaktif.' : 'Akun tidak ditemukan atau merupakan Owner aktif terakhir.');
    return $ok ? 0 : 1;
})->purpose('Nonaktifkan akses staf tanpa menghapus riwayat transaksi');
