<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // using raw sql to avoid doctrine/dbal dependency issues
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) DEFAULT 'student'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             // Revert back to ENUM if needed, though hazardous if data exists not in enum
             // For safety in dev, we might just leave it or try to revert best effort
             DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'student', 'employee', 'session_manager', 'organization_admin') DEFAULT 'student'");
        });
    }
};
