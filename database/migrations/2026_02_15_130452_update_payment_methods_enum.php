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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_method', 50)->change();
        });

        Schema::table('debt_payments', function (Blueprint $table) {
            $table->string('payment_method', 50)->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('payment_method', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'evc_plus', 'shilin_somali', 'mixed') NOT NULL");
        DB::statement("ALTER TABLE debt_payments MODIFY COLUMN payment_method ENUM('cash', 'evc_plus', 'shilin_somali') NOT NULL");
        DB::statement("ALTER TABLE expenses MODIFY COLUMN payment_method ENUM('cash', 'bank', 'evc_plus') NOT NULL");
    }
};
