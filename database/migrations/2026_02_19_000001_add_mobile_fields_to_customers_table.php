<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('full_name');
            $table->string('password')->nullable()->after('notes');
            $table->boolean('is_mobile_user')->default(false)->after('password');
            $table->string('profile_image')->nullable()->after('is_mobile_user');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['username', 'password', 'is_mobile_user', 'profile_image']);
        });
    }
};
