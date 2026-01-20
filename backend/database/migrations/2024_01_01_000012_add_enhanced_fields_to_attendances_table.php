<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add enhanced fields to attendances table
 * 
 * Adds fields for:
 * - Enhanced attendance states (present, late, absent, pending, rejected)
 * - Entry/Exit tracking
 * - Admin approval workflow
 * - Rejection reasons
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Change status enum to include new states
            $table->dropColumn('status');
        });
        
        Schema::table('attendances', function (Blueprint $table) {
            // Enhanced status with more states
            $table->enum('status', ['present', 'late', 'absent', 'pending', 'rejected'])->default('pending')->after('verification_method')->comment('Attendance status');
            
            // Entry/Exit tracking
            $table->enum('entry_type', ['entry', 'exit'])->default('entry')->after('status')->comment('Whether this is entry or exit attendance');
            $table->timestamp('exit_time')->nullable()->after('entry_type')->comment('Exit time for entry/exit tracking');
            
            // Admin approval
            $table->text('admin_notes')->nullable()->after('rejection_reason')->comment('Notes from admin (approval/rejection reason)');
            $table->foreignId('approved_by')->nullable()->after('admin_notes')->constrained('users')->onDelete('set null')->comment('Admin who approved/rejected');
            $table->timestamp('approved_at')->nullable()->after('approved_by')->comment('When the attendance was approved/rejected');
            
            // Additional indexes
            $table->index(['session_id', 'status']);
            $table->index(['user_id', 'verified_at']);
            $table->index('entry_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['session_id', 'status']);
            $table->dropIndex(['user_id', 'verified_at']);
            $table->dropIndex(['entry_type']);
            $table->dropColumn([
                'entry_type',
                'exit_time',
                'admin_notes',
                'approved_by',
                'approved_at'
            ]);
            $table->dropColumn('status');
        });
        
        Schema::table('attendances', function (Blueprint $table) {
            // Restore original status enum
            $table->enum('status', ['pending', 'verified', 'rejected', 'flagged'])->default('pending');
        });
    }
};
