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
            $table->string('status', 20)->default('completed')->after('video');
            $table->unsignedTinyInteger('progress')->default(100)->after('status');
            $table->string('step_label')->nullable()->after('progress');
            $table->string('task_id_photo')->nullable()->after('step_label');
            $table->string('task_id_video')->nullable()->after('task_id_photo');
            $table->boolean('generate_video')->default(true)->after('task_id_video');
            $table->text('error_message')->nullable()->after('generate_video');

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_virtual_try_ons', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn([
                'status',
                'progress',
                'step_label',
                'task_id_photo',
                'task_id_video',
                'generate_video',
                'error_message',
            ]);
        });
    }
};
