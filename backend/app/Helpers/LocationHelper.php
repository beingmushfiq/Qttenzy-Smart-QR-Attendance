<?php

namespace App\Helpers;

class LocationHelper
{
    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in meters
     */
    public static function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if location is within radius
     */
    public static function isWithinRadius(
        float $userLat,
        float $userLng,
        float $venueLat,
        float $venueLng,
        int $radiusMeters
    ): bool {
        $distance = self::calculateDistance($userLat, $userLng, $venueLat, $venueLng);
        return $distance <= $radiusMeters;
    }
}

