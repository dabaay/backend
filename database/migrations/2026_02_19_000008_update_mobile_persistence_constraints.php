<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update chat_messages
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('customer_id')->on('customerApp')->onDelete('cascade');
        });

        // Update product_ratings
        Schema::table('product_ratings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('customer_id')->on('customerApp')->onDelete('cascade');
        });

        // Update customer_favorites
        Schema::table('customer_favorites', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('customer_id')->on('customerApp')->onDelete('cascade');
        });

        // Update sales
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_app_id')->nullable()->after('customer_id');
            $table->foreign('customer_app_id')->references('customer_id')->on('customerApp')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_app_id']);
            $table->dropColumn('customer_app_id');
        });

        Schema::table('customer_favorites', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
        });

        Schema::table('product_ratings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
        });
    }
};
