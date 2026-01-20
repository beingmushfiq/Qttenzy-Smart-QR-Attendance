<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create role_user pivot table
 * 
 * This table links users to their roles (many-to-many relationship).
 * A user can have multiple roles (e.g., teacher and admin).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Reference to users table');
            $table->foreignId('role_id')->constrained()->onDelete('cascade')->comment('Reference to roles table');
            $table->timestamps();
            
            // Composite primary key
            $table->primary(['user_id', 'role_id']);
            
            // Indexes for faster lookups
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
