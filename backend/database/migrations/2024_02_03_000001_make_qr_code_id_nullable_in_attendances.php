<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * This migration makes qr_code_id nullable to support Face Authentication without QR code
     */
    public function up(): void
    {
        // Drop the foreign key constraint first
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['qr_code_id']);
        });

        // Modify the column to be nullable
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('qr_code_id')->nullable()->change();
        });

        // Re-add the foreign key constraint with SET NULL on delete
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('qr_code_id')
                ->references('id')
                ->on('qr_codes')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['qr_code_id']);
        });

        // Make the column NOT nullable again
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('qr_code_id')->nullable(false)->change();
        });

        // Re-add the foreign key constraint with CASCADE on delete
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('qr_code_id')
                ->references('id')
                ->on('qr_codes')
                ->onDelete('cascade');
        });
    }
};
