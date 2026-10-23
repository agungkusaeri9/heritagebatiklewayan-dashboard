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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_deleted')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_deleted')->default(false)->after('password');
            });
        }

        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'is_deleted')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('is_deleted')->default(false)->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_deleted')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_deleted');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'is_deleted')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('is_deleted');
            });
        }
    }
};
