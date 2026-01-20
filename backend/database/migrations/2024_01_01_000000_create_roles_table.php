<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create roles table
 * 
 * This table stores all available roles in the system.
 * Roles define what a user can do (admin, teacher, student, etc.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Unique role identifier (e.g., admin, teacher, student)');
            $table->string('display_name')->comment('Human-readable role name');
            $table->text('description')->nullable()->comment('Description of what this role can do');
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
