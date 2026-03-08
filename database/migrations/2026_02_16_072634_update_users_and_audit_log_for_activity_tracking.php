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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active') && !Schema::hasColumn('users', 'is_enabled')) {
                $table->renameColumn('is_active', 'is_enabled');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('is_enabled');
            }
            if (!Schema::hasColumn('users', 'last_logout_at')) {
                $table->timestamp('last_logout_at')->nullable()->after('last_login');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'active_hours')) {
                $table->decimal('active_hours', 10, 2)->default(0.00)->after('user_agent');
            }
            if (!Schema::hasColumn('audit_logs', 'inactive_hours')) {
                $table->decimal('inactive_hours', 10, 2)->default(0.00)->after('active_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['active_hours', 'inactive_hours']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_online')) {
                $table->dropColumn('is_online');
            }
            if (Schema::hasColumn('users', 'last_logout_at')) {
                $table->dropColumn('last_logout_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_enabled') && !Schema::hasColumn('users', 'is_active')) {
                $table->renameColumn('is_enabled', 'is_active');
            }
        });
    }
};
