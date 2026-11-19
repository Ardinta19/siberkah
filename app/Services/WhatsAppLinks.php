<?php

namespace App\Services;

use App\Models\Pesanan;

final class WhatsAppLinks
{
    public static function contact(string $message = ''): ?string
    {
        return self::link((string) config('services.whatsapp.admin_number'), $message);
    }

    public static function order(Pesanan $order, bool $toCustomer): ?string
    {
        $text = sprintf('Halo, terkait pesanan %s atas nama %s. Status: %s.', $order->nomor, $order->nama_pemesan, str_replace('_', ' ', $order->status));
        return $toCustomer ? self::link((string) $order->nomor_wa_pemesan, $text) : self::contact($text);
    }

    private static function link(string $number, string $message): ?string
    {
        if (! preg_match('/^628\d{7,12}$/D', $number)) {
            return null;
        }
        return 'https://wa.me/'.$number.($message === '' ? '' : '?text='.rawurlencode(mb_substr($message, 0, 1000)));
    }
}
