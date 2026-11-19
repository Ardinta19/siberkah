<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class RentalSchedule
{
    public static function permits(string $day, ?string $method, ?string $time, CarbonImmutable $now): bool
    {
        if ($day === $now->toDateString()) {
            if (!in_array($method, ['spidol','templat'], true) || ! $time) { return false; }
            $slot = $now->setTimeFromTimeString($time);
            return $time >= '07:00' && $time <= '17:00' && $slot->gte($now->addHours(3));
        }
        return $time === null && $day >= $now->startOfDay()->addDays($now->gt($now->setTime(15, 0)) ? 2 : 1)->toDateString();
    }

    public static function searchable(string $day): bool
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        return self::permits($day, 'spidol', $day === $now->toDateString() ? '17:00' : null, $now);
    }
}
