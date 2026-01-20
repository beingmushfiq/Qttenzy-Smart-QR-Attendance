<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create organizations table
 * 
 * This table stores organizations/institutions using the system.
 * Enables multi-tenant support where each organization has its own users and sessions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Organization name');
            $table->string('code')->unique()->comment('Unique organization code/identifier');
            $table->text('address')->nullable()->comment('Physical address');
            $table->string('phone')->nullable()->comment('Contact phone number');
            $table->string('email')->nullable()->comment('Contact email');
            $table->string('logo')->nullable()->comment('Organization logo path');
            $table->boolean('is_active')->default(true)->comment('Whether organization is active');
            $table->json('settings')->nullable()->comment('Organization-specific settings');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');
            
            // Indexes for faster lookups
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
