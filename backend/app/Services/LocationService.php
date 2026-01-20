<?php

namespace App\Services;

/**
 * LocationService
 * 
 * Handles GPS location validation and distance calculations.
 * Uses Haversine formula for accurate distance calculation.
 */
class LocationService
{
    /**
     * Earth's radius in meters
     */
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Validate if user's location is within allowed radius of venue
     * 
     * @param float $userLat User's latitude
     * @param float $userLng User's longitude
     * @param float $venueLat Venue's latitude
     * @param float $venueLng Venue's longitude
     * @param int $radiusMeters Allowed radius in meters
     * @return array Validation result with distance
     */
    public function validateLocation(
        float $userLat,
        float $userLng,
        float $venueLat,
        float $venueLng,
        int $radiusMeters
    ): array {
        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance($userLat, $userLng, $venueLat, $venueLng);

        $valid = $distance <= $radiusMeters;

        return [
            'valid' => $valid,
            'distance' => round($distance, 2),
            'allowed_radius' => $radiusMeters,
            'message' => $valid 
                ? 'Location verified successfully' 
                : "Location too far from venue ({$distance}m vs {$radiusMeters}m allowed)",
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * 
     * @param float $lat1 Latitude of point 1
     * @param float $lng1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lng2 Longitude of point 2
     * @return float Distance in meters
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLng = $lng2Rad - $lng1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = self::EARTH_RADIUS_METERS * $c;

        return $distance;
    }

    /**
     * Check if coordinates are valid
     * 
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return bool
     */
    public function validateCoordinates(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * Get location accuracy level
     * 
     * @param float $accuracy Accuracy in meters
     * @return string Accuracy level (high, medium, low)
     */
    public function getAccuracyLevel(float $accuracy): string
    {
        if ($accuracy <= 10) {
            return 'high';
        } elseif ($accuracy <= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
