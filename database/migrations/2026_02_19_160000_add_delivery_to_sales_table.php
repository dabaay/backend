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
            $table->enum('delivery_type', ['none', 'bajaj', 'vekon', 'plane'])->default('none')->after('customer_address');
            $table->decimal('delivery_price', 10, 2)->default(0.00)->after('delivery_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'delivery_price']);
        });
    }
};
