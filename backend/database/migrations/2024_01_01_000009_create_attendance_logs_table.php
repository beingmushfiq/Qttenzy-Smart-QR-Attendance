<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create attendance_logs table
 * 
 * This table tracks all changes to attendance records.
 * Provides an audit trail for attendance approvals, rejections, and modifications.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade')->comment('Reference to attendance record');
            $table->foreignId('user_id')->constrained()->comment('User who made the change');
            $table->string('action')->comment('Action performed (created, updated, approved, rejected, etc.)');
            $table->enum('old_status', ['present', 'late', 'absent', 'pending', 'rejected'])->nullable()->comment('Previous status');
            $table->enum('new_status', ['present', 'late', 'absent', 'pending', 'rejected'])->nullable()->comment('New status');
            $table->text('notes')->nullable()->comment('Reason for change or additional notes');
            $table->json('metadata')->nullable()->comment('Additional metadata (IP, user agent, etc.)');
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index(['attendance_id', 'created_at']);
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
