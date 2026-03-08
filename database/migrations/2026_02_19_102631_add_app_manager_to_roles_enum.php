<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add app_manager to the enum role
        \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'app_manager') NOT NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier') NOT NULL");
    }
};
