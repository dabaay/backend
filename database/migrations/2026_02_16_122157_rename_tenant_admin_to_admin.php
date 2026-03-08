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
        DB::table('users')->where('role', 'tenant_admin')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'tenant_admin']);
    }
};
