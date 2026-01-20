<?php

namespace App\Helpers;

class FaceHelper
{
    /**
     * Compare two face descriptors using Euclidean distance
     * Returns array with distance, match status, and score
     */
    public static function compareDescriptors(array $descriptor1, array $descriptor2): array
    {
        if (count($descriptor1) !== count($descriptor2)) {
            throw new \Exception('Descriptor dimensions do not match');
        }

        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sum += $diff * $diff;
        }

        $distance = sqrt($sum);
        $threshold = 0.6; // Configurable threshold
        $match = $distance < $threshold;
        $score = (1 - min($distance, 1)) * 100; // Convert to percentage

        return [
            'distance' => $distance,
            'match' => $match,
            'score' => max(0, min(100, $score)), // Clamp between 0-100
            'threshold' => $threshold
        ];
    }

    /**
     * Validate face descriptor format
     */
    public static function validateDescriptor(array $descriptor): bool
    {
        // Face descriptor should have 128 values
        if (count($descriptor) !== 128) {
            return false;
        }

        // All values should be numeric and between -1 and 1
        foreach ($descriptor as $value) {
            if (!is_numeric($value) || $value < -1 || $value > 1) {
                return false;
            }
        }

        return true;
    }
}

