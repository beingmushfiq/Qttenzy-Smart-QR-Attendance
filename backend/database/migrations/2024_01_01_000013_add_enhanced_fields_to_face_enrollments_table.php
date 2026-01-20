<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add enhanced fields to face_enrollments table
 * 
 * Adds fields for:
 * - Encrypted face descriptors (AES encryption)
 * - Confidence threshold configuration
 * - Re-verification tracking
 * - Encryption key rotation support
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('face_enrollments', function (Blueprint $table) {
            // Encryption key rotation support
            $table->string('encryption_key_id')->default('v1')->after('encrypted_descriptor')->comment('Encryption key version for rotation');
            
            // Confidence threshold
            $table->float('confidence_threshold')->default(0.7)->after('encryption_key_id')->comment('Minimum confidence score for verification');
            
            // Re-verification tracking
            $table->integer('verification_count')->default(0)->after('confidence_threshold')->comment('Number of times this face has been verified');
            $table->timestamp('last_verified_at')->nullable()->after('verification_count')->comment('Last time face was verified');
            $table->boolean('requires_reverification')->default(false)->after('last_verified_at')->comment('Whether face needs to be re-enrolled');
            
            // Indexes
            $table->index('requires_reverification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('face_enrollments', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['requires_reverification']);
            $table->dropColumn([
                'encryption_key_id',
                'confidence_threshold',
                'verification_count',
                'last_verified_at',
                'requires_reverification'
            ]);
        });
        
        Schema::table('face_enrollments', function (Blueprint $table) {
            $table->renameColumn('encrypted_descriptor', 'descriptor');
        });
    }
};
