<?php

namespace App\Services;

use App\Models\FaceEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * FaceVerificationService
 * 
 * Handles face recognition enrollment and verification with AES encryption.
 * Ensures biometric data is securely stored and never exposed in plain text.
 */
class FaceVerificationService
{
    /**
     * Encryption key version for key rotation support
     */
    private const ENCRYPTION_KEY_VERSION = 'v1';

    /**
     * Default confidence threshold for face matching
     */
    private const DEFAULT_THRESHOLD = 0.7;

    /**
     * Enroll face for user (one-time enrollment)
     * 
     * @param int $userId
     * @param array $descriptor Face descriptor array from Face-API.js
     * @param string|null $imagePath Path to stored face image
     * @return FaceEnrollment
     * @throws \Exception
     */
    /**
     * Enroll face for user (one-time enrollment)
     * 
     * @param int $userId
     * @param array $descriptor Face descriptor array from Face-API.js
     * @param string|null $imagePath Path to stored face image
     * @return User
     * @throws \Exception
     */
    public function enrollFace(int $userId, array $descriptor, ?string $imagePath = null): \App\Models\FaceEnrollment
    {
        // Validate descriptor format
        if (!$this->validateDescriptor($descriptor)) {
            throw new \Exception('Invalid face descriptor format. Expected 128-dimensional array.');
        }

        $user = User::findOrFail($userId);

        // Encrypt logic should match UserController
        $faceDescriptor = json_encode($descriptor);
        $encryptedDescriptor = encrypt($faceDescriptor);

        $enrollment = \App\Models\FaceEnrollment::updateOrCreate(
            ['user_id' => $userId],
            [
                'encrypted_descriptor' => $encryptedDescriptor,
                'image_path' => $imagePath,
                'confidence_threshold' => 0.7, // Matching Score threshold
                'requires_reverification' => false,
                'verification_count' => 0
            ]
        );

        // Sync flags on User model if we still use them for quick checks
        $user->face_enrolled = true;
        // $user->face_descriptor = ...; // We don't save this on User anymore
        $user->save();

        Log::info('Face enrolled successfully via Service', [
            'user_id' => $userId
        ]);

        return $enrollment;
    }

    /**
     * Verify face against enrolled face
     * 
     * @param int $userId
     * @param array $currentDescriptor Current face descriptor to verify
     * @return array Verification result with match status and score
     */
    public function verifyFace(int $userId, array $currentDescriptor): array
    {
        // Validate descriptor format
        if (!$this->validateDescriptor($currentDescriptor)) {
            return [
                'match' => false,
                'score' => 0,
                'message' => 'Invalid face descriptor format',
                'threshold' => self::DEFAULT_THRESHOLD,
            ];
        }

        // Get user enrollment
        $enrollment = \App\Models\FaceEnrollment::where('user_id', $userId)->first();

        if (!$enrollment) {
            return [
                'match' => false,
                'score' => 0,
                'message' => 'No face enrollment found for this user',
                'threshold' => self::DEFAULT_THRESHOLD,
            ];
        }

        // Decode descriptor
        try {
            // Decrypt the stored descriptor
            // Stored format: encrypt(json_encode([1, 2, ...]))
            $decryptedJson = decrypt($enrollment->encrypted_descriptor);
            $enrolledDescriptor = json_decode($decryptedJson, true);

            if (!is_array($enrolledDescriptor)) {
                 throw new \Exception("Decoded descriptor is not an array");
            }

        } catch (\Exception $e) {
            Log::error('Face decryption failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
             return [
                'match' => false,
                'score' => 0,
                'message' => 'Failed to parse enrolled face data',
                'threshold' => self::DEFAULT_THRESHOLD,
            ];
        }

        // Calculate similarity (Euclidean distance)
        $distance = $this->calculateEuclideanDistance($enrolledDescriptor, $currentDescriptor);
        
        // Convert distance to similarity score (0-1 range)
        // FaceAPI matches are typically < 0.6. 
        // 0.0 distance = 100% score.
        // 0.6 distance = ~70% score?
        // Let's use a simpler mapping: Score = max(0, 1 - distance) (where 0.4 distance = 0.6 score, which is somewhat harsh).
        // Better: Score = max(0, 1 - (distance / 0.8)) where 0.8 is "zero match".
        // Actually, let's keep previous logic if it was sane, OR improve it.
        // Previous: max(0, 1 - ($distance / 2)) -> Distance 0.6 = 0.7 score. Matches threshold 0.7.
        // That seems fine.
        $score = max(0, 1 - ($distance / 2)); 
        
        // Check against threshold (Use enrollment specific threshold if available, else default)
        $threshold = $enrollment->confidence_threshold ?? self::DEFAULT_THRESHOLD;
        
        // FaceAPI: distance < 0.6 is a match.
        // Score: (1 - 0.6/2) = 0.7. 
        // So Score >= 0.7 means Distance <= 0.6.
        $match = $score >= $threshold;

        Log::info('Face verification completed', [
            'user_id' => $userId,
            'match' => $match,
            'score' => round($score, 4),
            'distance' => round($distance, 4),
            'threshold' => $threshold,
        ]);

        return [
            'match' => $match,
            'score' => round($score, 4),
            'distance' => round($distance, 4),
            'threshold' => $threshold,
            'message' => $match ? 'Face verified successfully' : 'Face verification failed',
        ];
    }

    /**
     * Validate face descriptor format
     * 
     * @param array $descriptor
     * @return bool
     */
    private function validateDescriptor(array $descriptor): bool
    {
        // Face-API.js descriptors are 128-dimensional arrays
        if (count($descriptor) !== 128) {
            return false;
        }

        // All elements should be numeric
        foreach ($descriptor as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate Euclidean distance between two descriptors
     * 
     * @param array $descriptor1
     * @param array $descriptor2
     * @return float Distance value
     */
    private function calculateEuclideanDistance(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            throw new \Exception('Descriptors must have the same dimensions');
        }

        $sumSquares = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sumSquares += $diff * $diff;
        }

        return sqrt($sumSquares);
    }

    /**
     * Re-enroll face (for key rotation or quality improvement)
     * 
     * @param int $userId
     * @param array $newDescriptor
     * @param string|null $imagePath
     * @return \App\Models\FaceEnrollment
     */
    public function reEnrollFace(int $userId, array $newDescriptor, ?string $imagePath = null): \App\Models\FaceEnrollment
    {
        // For User model implementation, re-enroll is just enroll (overwrite)
        return $this->enrollFace($userId, $newDescriptor, $imagePath);
    }

    /**
     * Check if user has face enrolled
     * 
     * @param int $userId
     * @return bool
     */
    public function hasFaceEnrolled(int $userId): bool
    {
        // Check both flag and actual record existence
        $user = User::find($userId);
        if (!$user) return false;

        // Optimization: rely on flag, but if flag is true, ensure record exists
        if ($user->face_enrolled) {
            return \App\Models\FaceEnrollment::where('user_id', $userId)->exists();
        }
        return false;
    }

    /**
     * Delete face enrollment
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteFaceEnrollment(int $userId): bool
    {
        $user = User::find($userId);
        if ($user) {
            // Delete FaceEnrollment record
            \App\Models\FaceEnrollment::where('user_id', $userId)->delete();

            // Clear User flags
            $user->face_descriptor = null; // Legacy cleanup
            $user->face_enrolled = false;
            $user->face_consent = false;
            return $user->save();
        }
        return false;
    }
}

