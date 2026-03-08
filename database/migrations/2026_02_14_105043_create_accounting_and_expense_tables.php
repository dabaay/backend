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
        // 1. Expenses Table
        Schema::create('expenses', function (Blueprint $table) {
            $table->id('expense_id');
            $table->string('expense_number')->unique();
            $table->enum('expense_category', ['rent', 'electricity', 'water', 'tax', 'salary', 'maintenance', 'transport', 'marketing', 'office', 'other']);
            $table->decimal('amount', 12, 2);
            $table->text('description');
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'bank', 'evc_plus'])->default('cash');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('document_path')->nullable();
            $table->foreignId('requested_by')->constrained('users', 'user_id');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 2. Debts Table
        Schema::create('debts', function (Blueprint $table) {
            $table->id('debt_id');
            $table->foreignId('customer_id')->constrained('customers', 'customer_id');
            $table->foreignId('sale_id')->constrained('sales', 'sale_id');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'written_off'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Debt Payments Table
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('debt_id')->constrained('debts', 'debt_id');
            $table->decimal('amount_paid', 12, 2);
            $table->timestamp('payment_date')->useCurrent();
            $table->string('payment_method');
            $table->foreignId('received_by')->constrained('users', 'user_id');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Financial Transactions Table
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->timestamp('transaction_date')->useCurrent();
            $table->string('transaction_type');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_table')->nullable();
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Daily Summaries Table
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id('summary_id');
            $table->date('summary_date')->unique();
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('total_cash_sales', 12, 2)->default(0);
            $table->decimal('total_evc_sales', 12, 2)->default(0);
            $table->decimal('total_shilin_sales', 12, 2)->default(0);
            $table->decimal('total_expenses', 12, 2)->default(0);
            $table->decimal('total_debt_collected', 12, 2)->default(0);
            $table->decimal('total_debt_created', 12, 2)->default(0);
            $table->decimal('total_profit', 12, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->integer('customer_count')->default(0);
            $table->timestamps();
        });

        // 6. Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id');
            $table->string('action');
            $table->string('table_name')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // 7. Notifications Table
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['info', 'success', 'warning', 'danger'])->default('info');
            $table->boolean('is_read')->default(false);
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('daily_summaries');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('debts');
        Schema::dropIfExists('expenses');
    }
};
