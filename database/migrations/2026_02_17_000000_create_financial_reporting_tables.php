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
        // 1. Fixed Assets Table
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id('asset_id');
            $table->string('asset_name', 200);
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 12, 2);
            $table->integer('useful_life_years');
            $table->string('depreciation_method', 50)->default('straight_line'); // straight_line, declining_balance
            $table->decimal('current_value', 12, 2);
            $table->decimal('accumulated_depreciation', 12, 2)->default(0.00);
            $table->enum('status', ['active', 'sold', 'disposed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Capital Transactions Table (Owner's Equity)
        Schema::create('capital_transactions', function (Blueprint $table) {
            $table->id('capital_id');
            $table->date('transaction_date');
            $table->enum('transaction_type', ['investment', 'drawing']); // investment = add to equity, drawing = remove from equity
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_transactions');
        Schema::dropIfExists('fixed_assets');
    }
};
