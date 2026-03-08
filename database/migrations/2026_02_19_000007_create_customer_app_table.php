<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customerApp', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('phone')->unique();
            $table->string('address')->nullable();
            $table->string('password');
            $table->string('profile_image')->nullable();
            $table->string('status')->default('active');
            $table->datetime('registration_date');
            $table->decimal('current_balance', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customerApp');
    }
};
