<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->foreignId('qr_code_id')->constrained('qr_codes')->onDelete('cascade');
            $table->dateTime('verified_at');
            $table->decimal('face_match_score', 5, 2)->nullable();
            $table->boolean('face_match')->default(false);
            $table->boolean('gps_valid')->default(false);
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->decimal('distance_from_venue', 10, 2)->nullable(); // meters
            $table->string('ip_address', 45)->nullable();
            $table->text('device_info')->nullable();
            $table->boolean('webauthn_used')->default(false);
            $table->enum('verification_method', [
                'qr_only',
                'qr_face',
                'qr_face_gps',
                'qr_face_gps_webauthn'
            ])->default('qr_face_gps');
            $table->enum('status', ['pending', 'verified', 'rejected', 'flagged'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'session_id']);
            $table->index(['user_id', 'session_id', 'verified_at']);
            // Index moved to enhanced migration to avoid conflicts
            $table->index('verified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

