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
