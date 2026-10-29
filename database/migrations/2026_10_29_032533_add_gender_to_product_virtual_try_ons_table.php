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
        Schema::table('product_virtual_try_ons', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female', 'unisex'])->nullable()->after('virtual_try_on_model_id');
            $table->index(['product_id', 'gender']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_virtual_try_ons', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'gender']);
            $table->dropColumn('gender');
        });
    }
};
