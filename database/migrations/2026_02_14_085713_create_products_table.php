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
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_code', 50)->unique()->nullable();
            $table->string('barcode', 50)->unique()->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'supplier_id');
            $table->decimal('cost_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->integer('current_stock')->default(0);
            $table->integer('minimum_stock')->default(5);
            $table->integer('maximum_stock')->default(100);
            $table->string('unit', 20)->default('piece');
            $table->string('location', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
