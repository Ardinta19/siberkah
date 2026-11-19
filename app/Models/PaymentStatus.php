<?php

namespace App\Models;

/**
 * Status pembayaran - digunakan di model Pembayaran untuk validasi enum
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
}
