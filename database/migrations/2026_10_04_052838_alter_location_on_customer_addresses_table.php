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
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['province', 'city']);
            $table->integer('province_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('subdistrict_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['province_id', 'city_id', 'subdistrict_id']);
            $table->string('province')->nullable();
            $table->string('city')->nullable();
        });
    }
};
