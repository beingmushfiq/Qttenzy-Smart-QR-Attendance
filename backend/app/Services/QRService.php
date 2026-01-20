<?php

namespace App\Services;

use App\Models\QRCode;
use App\Models\Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QR;
use Carbon\Carbon;

class QRService
{
    /**
     * Generate QR code for a session
     */
    public function generateQR(int $sessionId): array
    {
        $session = Session::findOrFail($sessionId);
        
        // Generate unique code
        $code = $this->generateUniqueCode($sessionId);
        
        // Set expiration (5 minutes default, or session end time)
        $expiresAt = min(
            now()->addMinutes(5),
            Carbon::parse($session->end_time)
        );

        $qrCode = QRCode::create([
            'session_id' => $sessionId,
            'code' => $code,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'rotation_interval' => 300 // 5 minutes
        ]);

        // Return code without image generation to avoid GD dependency
        return [
            'qr_code' => $code,
            'qr_text' => $code, // Text representation
            'expires_at' => $expiresAt,
            'session_id' => $sessionId
        ];
    }

    /**
     * Validate QR code
     */
    public function validateQR(string $code, int $sessionId): array
    {
        $qrCode = QRCode::where('code', $code)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('session')
            ->first();

        if (!$qrCode) {
            return ['valid' => false, 'message' => 'Invalid or expired QR code'];
        }

        return [
            'valid' => true,
            'qr_code_id' => $qrCode->id,
            'session' => $qrCode->session
        ];
    }

    /**
     * Generate unique QR code string
     */
    private function generateUniqueCode(int $sessionId): string
    {
        return 'SESSION_' . $sessionId . '_' . time() . '_' . bin2hex(random_bytes(4));
    }

    /**
     * Rotate QR code for a session
     */
    public function rotateQR(int $sessionId): void
    {
        // Deactivate old QR codes
        QRCode::where('session_id', $sessionId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Generate new QR code
        $this->generateQR($sessionId);
    }
}

