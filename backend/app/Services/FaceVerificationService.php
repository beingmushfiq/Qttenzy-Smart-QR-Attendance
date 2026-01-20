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
    public function enrollFace(int $userId, array $descriptor, ?string $imagePath = null): FaceEnrollment
    {
        // Validate descriptor format
        if (!$this->validateDescriptor($descriptor)) {
            throw new \Exception('Invalid face descriptor format. Expected 128-dimensional array.');
        }

        // Check if user already has face enrolled
        $existingEnrollment = FaceEnrollment::where('user_id', $userId)->first();
        if ($existingEnrollment) {
            throw new \Exception('User already has face enrolled. Only one enrollment per user is allowed.');
        }

        // Check user consent
        $user = User::findOrFail($userId);
        if (!$user->face_consent) {
            throw new \Exception('User has not provided consent for face recognition.');
        }

        // Encrypt the descriptor
        $encryptedDescriptor = $this->encryptDescriptor($descriptor);

        // Create enrollment
        $enrollment = FaceEnrollment::create([
            'user_id' => $userId,
            'encrypted_descriptor' => $encryptedDescriptor,
            'encryption_key_id' => self::ENCRYPTION_KEY_VERSION,
            'image_path' => $imagePath,
            'confidence_threshold' => self::DEFAULT_THRESHOLD,
            'verification_count' => 0,
            'requires_reverification' => false,
        ]);

        Log::info('Face enrolled successfully', [
            'user_id' => $userId,
            'enrollment_id' => $enrollment->id,
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

        // Get user's face enrollment
        $enrollment = FaceEnrollment::where('user_id', $userId)
            ->where('requires_reverification', false)
            ->first();

        if (!$enrollment) {
            return [
                'match' => false,
                'score' => 0,
                'message' => 'No face enrollment found for this user',
                'threshold' => self::DEFAULT_THRESHOLD,
            ];
        }

        // Decrypt enrolled descriptor
        try {
            $enrolledDescriptor = $this->decryptDescriptor($enrollment->encrypted_descriptor);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt face descriptor', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'match' => false,
                'score' => 0,
                'message' => 'Failed to decrypt enrolled face data',
                'threshold' => $enrollment->confidence_threshold,
            ];
        }

        // Calculate similarity (Euclidean distance)
        $distance = $this->calculateEuclideanDistance($enrolledDescriptor, $currentDescriptor);
        
        // Convert distance to similarity score (0-1 range)
        // Lower distance = higher similarity
        $score = max(0, 1 - ($distance / 2)); // Normalize to 0-1 range
        
        // Check against threshold
        $threshold = $enrollment->confidence_threshold;
        $match = $score >= $threshold;

        // Update verification count and last verified time
        if ($match) {
            $enrollment->increment('verification_count');
            $enrollment->update(['last_verified_at' => now()]);
        }

        Log::info('Face verification completed', [
            'user_id' => $userId,
            'match' => $match,
            'score' => round($score, 4),
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
     * Encrypt face descriptor using AES encryption
     * 
     * @param array $descriptor
     * @return string Encrypted descriptor
     */
    private function encryptDescriptor(array $descriptor): string
    {
        $json = json_encode($descriptor);
        return Crypt::encryptString($json);
    }

    /**
     * Decrypt face descriptor
     * 
     * @param string $encryptedDescriptor
     * @return array Decrypted descriptor
     * @throws \Exception
     */
    private function decryptDescriptor(string $encryptedDescriptor): array
    {
        $json = Crypt::decryptString($encryptedDescriptor);
        return json_decode($json, true);
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
     * @return FaceEnrollment
     */
    public function reEnrollFace(int $userId, array $newDescriptor, ?string $imagePath = null): FaceEnrollment
    {
        // Mark old enrollment for reverification
        FaceEnrollment::where('user_id', $userId)
            ->update(['requires_reverification' => true]);

        // Create new enrollment
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
        return FaceEnrollment::where('user_id', $userId)
            ->where('requires_reverification', false)
            ->exists();
    }

    /**
     * Delete face enrollment
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteFaceEnrollment(int $userId): bool
    {
        return FaceEnrollment::where('user_id', $userId)->delete() > 0;
    }
}

