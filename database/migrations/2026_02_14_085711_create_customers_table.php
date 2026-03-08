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
        Schema::create('customers', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('full_name', 100);
            $table->string('phone', 15)->unique();
            $table->text('address')->nullable();
            $table->string('id_number', 50)->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->decimal('current_balance', 12, 2)->default(0.00);
            $table->decimal('total_purchases', 12, 2)->default(0.00);
            $table->date('last_purchase_date')->nullable();
            $table->enum('status', ['active', 'blocked', 'inactive'])->default('active');
            $table->foreignId('registered_by')->nullable()->constrained('users', 'user_id');
            $table->timestamp('registration_date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
