<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('location_lat', 10, 8);
            $table->decimal('location_lng', 11, 8);
            $table->string('location_name', 255)->nullable();
            $table->integer('radius_meters')->default(100);
            $table->enum('session_type', ['admin_approved', 'pre_registered', 'open'])->default('open');
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->boolean('requires_payment')->default(false);
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->integer('max_attendees')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('status');
            $table->index('start_time');
            $table->index('created_by');
            $table->index(['location_lat', 'location_lng']);
            // Fulltext index removed - not supported by PostgreSQL
            // Use regular indexes on title for basic search functionality
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};

