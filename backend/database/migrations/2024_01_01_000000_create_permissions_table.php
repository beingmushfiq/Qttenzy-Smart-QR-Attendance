<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create permissions table
 * 
 * This table stores all available permissions in the system.
 * Permissions define specific actions (create_session, approve_attendance, etc.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Unique permission identifier (e.g., create_session)');
            $table->string('display_name')->comment('Human-readable permission name');
            $table->text('description')->nullable()->comment('Description of what this permission allows');
            $table->string('group')->nullable()->comment('Permission group for organization (e.g., sessions, attendance)');
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index('name');
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
