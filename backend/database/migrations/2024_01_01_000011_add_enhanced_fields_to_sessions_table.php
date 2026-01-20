<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add enhanced fields to sessions table
 * 
 * Adds fields for:
 * - Recurring sessions (daily, weekly, monthly)
 * - Capacity management
 * - Entry/Exit tracking
 * - Late marking logic
 * - Organization support
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Organization support
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->onDelete('cascade')->comment('Organization this session belongs to');
            
            // Recurring sessions
            $table->enum('recurrence_type', ['one_time', 'daily', 'weekly', 'monthly'])->default('one_time')->after('session_type')->comment('Session recurrence pattern');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_type')->comment('When recurring sessions should stop');
            $table->foreignId('parent_session_id')->nullable()->after('recurrence_end_date')->constrained('sessions')->onDelete('cascade')->comment('Parent session for recurring instances');
            
            // Capacity management
            $table->integer('capacity')->nullable()->after('max_attendees')->comment('Maximum capacity (renamed from max_attendees)');
            $table->integer('current_count')->default(0)->after('capacity')->comment('Current attendance count');
            
            // Entry/Exit tracking
            $table->boolean('allow_entry_exit')->default(false)->after('current_count')->comment('Whether to track entry and exit times');
            
            // Late marking
            $table->integer('late_threshold_minutes')->default(15)->after('allow_entry_exit')->comment('Minutes after start time to mark as late');
            
            // Additional indexes
            $table->index(['status', 'start_time']);
            $table->index(['organization_id', 'created_by']);
            $table->index('recurrence_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['parent_session_id']);
            $table->dropIndex(['status', 'start_time']);
            $table->dropIndex(['organization_id', 'created_by']);
            $table->dropIndex(['recurrence_type']);
            $table->dropColumn([
                'organization_id',
                'recurrence_type',
                'recurrence_end_date',
                'parent_session_id',
                'capacity',
                'current_count',
                'allow_entry_exit',
                'late_threshold_minutes'
            ]);
        });
    }
};
