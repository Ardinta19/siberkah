<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/** One rolling window shared by issue and resend, across all web workers on the shared cache. */
final class OtpQuota
{
    public function consume(string $phone, string $ip): void
    {
        $numberKey = 'otp:q:n:'.hash_hmac('sha256', $phone, config('app.key'));
        $ipKey = 'otp:q:i:'.hash_hmac('sha256', $ip, config('app.key'));
        $numberLock = Cache::lock($numberKey.':lock', 10);
        abort_unless($numberLock->get(), 429, 'Coba lagi sebentar.');
        try {
            $ipLock = Cache::lock($ipKey.':lock', 10);
            abort_unless($ipLock->get(), 429, 'Coba lagi sebentar.');
            try {
                $now = now()->timestamp;
                $recent = fn ($key) => array_values(array_filter(Cache::get($key, []), fn ($stamp) => $stamp > $now - 900));
                $numbers = $recent($numberKey);
                $ips = $recent($ipKey);
                abort_if(count($numbers) >= 3 || count($ips) >= 3, 429, 'Batas tiga permintaan OTP dalam 15 menit tercapai.');
                abort_if($numbers && max($numbers) > $now - 60, 429, 'Tunggu satu menit sebelum meminta kode lagi.');
                $numbers[] = $ips[] = $now;
                Cache::put($numberKey, $numbers, 900);
                Cache::put($ipKey, $ips, 900);
            } finally {
                $ipLock->release();
            }
        } finally {
            $numberLock->release();
        }
    }
}
