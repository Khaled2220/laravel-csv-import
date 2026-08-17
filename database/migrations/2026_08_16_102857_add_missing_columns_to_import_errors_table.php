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
        Schema::table('import_errors', function (Blueprint $table) {
            $table->foreignId('import_id')
                ->constrained('imports')
                ->cascadeOnDelete();

            $table->unsignedInteger('row_number');

            $table->json('row_data')->nullable();

            $table->text('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_errors', function (Blueprint $table) {
            $table->dropForeign(['import_id']);
            $table->dropColumn([
                'import_id',
                'row_number',
                'row_data',
                'error_message',
            ]);
        });
    }
};

