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
        if (Schema::hasTable('audit_log') && !Schema::hasTable('audit_logs')) {
            Schema::rename('audit_log', 'audit_logs');
        }

        if (!Schema::hasTable('audit_logs')) {
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
        }
    }

    public function down(): void
    {
        // No need to reverse as this is a fix
    }
};
