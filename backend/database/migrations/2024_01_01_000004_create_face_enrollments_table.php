<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('encrypted_descriptor')->comment('AES encrypted face descriptor'); // JSON array of face descriptor
            $table->string('image_url', 500)->nullable();
            $table->enum('enrollment_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('enrollment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_enrollments');
    }
};

