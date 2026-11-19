<?php

namespace App\Models;

/**
 * Tipe pembayaran - digunakan di model Pembayaran untuk validasi enum
 */
enum PaymentType: string
{
    case DP = 'dp';
    case PELUNASAN = 'pelunasan';
    case PENUH = 'penuh';
    case REFUND = 'refund';
}
