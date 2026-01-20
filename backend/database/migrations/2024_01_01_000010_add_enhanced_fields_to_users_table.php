<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add enhanced fields to users table
 * 
 * Adds fields for:
 * - Organization support (multi-tenant)
 * - User approval workflow
 * - Face consent tracking
 * - Soft deletes
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Organization support
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->onDelete('set null')->comment('Organization this user belongs to');
            
            // Approval workflow
            $table->boolean('requires_approval')->default(false)->after('is_active')->comment('Whether this user requires admin approval');
            $table->boolean('is_approved')->default(true)->after('requires_approval')->comment('Whether this user has been approved');
            $table->timestamp('approved_at')->nullable()->after('is_approved')->comment('When the user was approved');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->onDelete('set null')->comment('Admin who approved this user');
            
            // Face consent
            $table->boolean('face_consent')->default(false)->after('webauthn_credential_id')->comment('Whether user consented to face recognition');
            
            // Soft deletes
            $table->softDeletes()->after('updated_at')->comment('Soft delete timestamp');
            
            // Additional indexes
            $table->index(['email', 'organization_id']);
            $table->index('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['email', 'organization_id']);
            $table->dropIndex(['is_approved']);
            $table->dropColumn([
                'organization_id',
                'requires_approval',
                'is_approved',
                'approved_at',
                'approved_by',
                'face_consent',
                'deleted_at'
            ]);
        });
    }
};
