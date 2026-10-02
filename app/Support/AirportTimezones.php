<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeZone;

class AirportTimezones
{
    private static ?array $map = null;

    public static function has(string $code): bool
    {
        return isset(self::map()[$code]);
    }

    public static function timezone(string $code): DateTimeZone
    {
        return new DateTimeZone(self::map()[$code]);
    }

    public static function toUtc(string $localTime, string $code): CarbonImmutable
    {
        return CarbonImmutable::parse($localTime, self::timezone($code))->utc();
    }

    private static function map(): array
    {
        return self::$map ??= require resource_path('data/airport-timezones.php');
    }
}
