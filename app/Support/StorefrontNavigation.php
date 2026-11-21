<?php

namespace App\Support;

final class StorefrontNavigation
{
    /** Exact destination vs. a parent section; unrelated pages have no selected tab. */
    public static function current(string $destination): ?string
    {
        if (request()->routeIs($destination)) {
            return 'page';
        }

        return match ($destination) {
            'catalog.index' => request()->routeIs('catalog.show', 'cart.index', 'checkout.product') ? 'location' : null,
            'pesanan.index' => request()->routeIs('pesanan.show', 'payment.show') ? 'location' : null,
            default => null,
        };
    }
}
