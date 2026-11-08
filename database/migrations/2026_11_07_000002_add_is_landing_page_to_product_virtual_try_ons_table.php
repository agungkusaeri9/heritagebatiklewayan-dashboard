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
            $table->boolean('is_landing_page')->default(false)->index()->after('generate_video');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_virtual_try_ons', function (Blueprint $table) {
            $table->dropColumn('is_landing_page');
        });
    }
};
