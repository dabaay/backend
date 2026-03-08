<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_status ENUM('paid', 'partial', 'credit', 'pending', 'cancelled') DEFAULT 'paid'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_status ENUM('paid', 'partial', 'credit') DEFAULT 'paid'");
    }
};
