<?php

namespace App\Support;

class LaundryStatus
{
    public const RECEIVED = 'received';
    public const WASHING = 'washing';
    public const DRYING = 'drying';
    public const IRONING = 'ironing';
    public const PACKING = 'packing';
    public const READY_FOR_PICKUP = 'ready_for_pickup';
    public const PICKED_UP = 'picked_up';
    public const CANCELED = 'canceled';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return [
            self::RECEIVED,
            self::WASHING,
            self::DRYING,
            self::IRONING,
            self::PACKING,
            self::READY_FOR_PICKUP,
            self::PICKED_UP,
            self::CANCELED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::RECEIVED => 'Diterima',
            self::WASHING => 'Dicuci',
            self::DRYING => 'Dikeringkan',
            self::IRONING => 'Disetrika/Gosok',
            self::PACKING => 'Dipacking',
            self::READY_FOR_PICKUP => 'Siap Diambil',
            self::PICKED_UP => 'Sudah Diambil',
            self::CANCELED => 'Dibatalkan',
        ];
    }

    /**
     * @return array<string>
     */
    public static function workflow(): array
    {
        return [
            self::RECEIVED,
            self::WASHING,
            self::DRYING,
            self::IRONING,
            self::PACKING,
            self::READY_FOR_PICKUP,
            self::PICKED_UP,
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === self::CANCELED || $from === self::PICKED_UP) {
            return false;
        }

        if ($to === self::CANCELED) {
            return true;
        }

        $map = array_flip(self::workflow());

        if (! isset($map[$from], $map[$to])) {
            return false;
        }

        return $map[$to] >= $map[$from];
    }
}
