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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id('purchase_id');
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id');
            $table->decimal('total_amount', 12, 2);
            $table->enum('transport_method', ['Bajaj', 'Vekon', 'Car', 'Other'])->default('Other');
            $table->decimal('transport_cost', 10, 2)->default(0);
            $table->date('purchase_date');
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('received');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users', 'user_id');
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id('purchase_item_id');
            $table->foreignId('purchase_id')->constrained('purchases', 'purchase_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
