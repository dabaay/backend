<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the payment_method enum to include 'credit'
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'evc_plus', 'shilin_somali', 'mixed', 'credit')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'evc_plus', 'shilin_somali', 'mixed')");
    }
};
