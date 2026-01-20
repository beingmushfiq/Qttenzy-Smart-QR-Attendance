<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->string('code', 255)->unique();
            $table->dateTime('expires_at');
            $table->boolean('is_active')->default(true);
            $table->integer('rotation_interval')->default(300); // seconds
            $table->timestamps();

            $table->index('session_id');
            $table->index('code');
            $table->index('expires_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};

