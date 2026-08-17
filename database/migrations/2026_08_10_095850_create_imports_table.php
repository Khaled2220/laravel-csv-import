<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->string('file_name')->after('id');

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
            ])->default('pending')->after('file_path');

            $table->unsignedInteger('total_records')
                ->default(0)
                ->after('status');

            $table->unsignedInteger('processed_records')
                ->default(0)
                ->after('total_records');

            $table->unsignedInteger('failed_records')
                ->default(0)
                ->after('processed_records');

            $table->timestamp('started_at')
                ->nullable()
                ->after('failed_records');

            $table->timestamp('completed_at')
                ->nullable()
                ->after('started_at');

            $table->text('error_message')
                ->nullable()
                ->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn([
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