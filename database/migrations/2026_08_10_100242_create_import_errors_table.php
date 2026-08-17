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
        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
             $table->foreignId('import_id')
                ->constrained('imports')
                ->cascadeOnDelete();

            $table->unsignedInteger('row_number');

            $table->json('row_data')->nullable();

            $table->text('error_message');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
