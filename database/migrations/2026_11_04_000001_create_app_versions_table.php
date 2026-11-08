<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Heritage Batik Lewayan');
            $table->enum('type', ['android', 'ios'])->default('android')->index();
            $table->string('version', 50);
            $table->unsignedInteger('version_code')->default(1);
            $table->string('min_version', 50)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_latest')->default(false)->index();
            $table->boolean('is_force_update')->default(false);
            $table->boolean('is_maintenance')->default(false);
            $table->text('maintenance_message')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
