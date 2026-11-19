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
