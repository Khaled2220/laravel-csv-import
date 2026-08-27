<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();

            $table->string('file_name');

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
            ])->default('pending');

            $table->unsignedInteger('total_records')->default(0);

            $table->unsignedInteger('processed_records')->default(0);

            $table->unsignedInteger('failed_records')->default(0);

            $table->timestamp('started_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};