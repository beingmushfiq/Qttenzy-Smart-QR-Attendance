<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create audit_logs table
 * 
 * This table tracks all important actions in the system for security and compliance.
 * Logs user actions, data changes, and system events.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('User who performed the action');
            $table->string('action')->comment('Action performed (e.g., created, updated, deleted)');
            $table->string('model_type')->nullable()->comment('Model class name (e.g., App\Models\Session)');
            $table->unsignedBigInteger('model_id')->nullable()->comment('ID of the affected model');
            $table->json('old_values')->nullable()->comment('Previous values before change');
            $table->json('new_values')->nullable()->comment('New values after change');
            $table->string('ip_address', 45)->nullable()->comment('IP address of the user');
            $table->text('user_agent')->nullable()->comment('Browser user agent');
            $table->text('notes')->nullable()->comment('Additional notes or context');
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
