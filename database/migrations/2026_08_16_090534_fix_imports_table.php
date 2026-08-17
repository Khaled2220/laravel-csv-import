<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

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
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            $table->dropColumn([
                'user_id',
                'file_name',
                'status',
                'total_records',
                'processed_records',
                'failed_records',
                'started_at',
                'completed_at',
                'error_message',
            ]);
        });
    }
};
