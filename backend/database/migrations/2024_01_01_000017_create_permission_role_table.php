<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create permission_role pivot table
 * 
 * This table links roles to their permissions (many-to-many relationship).
 * Defines which permissions each role has.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->onDelete('cascade')->comment('Reference to permissions table');
            $table->foreignId('role_id')->constrained()->onDelete('cascade')->comment('Reference to roles table');
            $table->timestamps();
            
            // Composite primary key
            $table->primary(['permission_id', 'role_id']);
            
            // Indexes for faster lookups
            $table->index('permission_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
