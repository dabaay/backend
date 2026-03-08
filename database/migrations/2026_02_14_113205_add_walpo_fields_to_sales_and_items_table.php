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
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_walpo')->default(false)->after('payment_status');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->integer('taken_quantity')->default(0)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('is_walpo');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('taken_quantity');
        });
    }
};
