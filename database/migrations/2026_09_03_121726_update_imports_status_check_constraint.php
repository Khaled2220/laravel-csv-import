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
        Schema::create('imports', function (Blueprint $table) 
        {
            $table->id();
            $table->foreignId('user_id') 
            ->constrained('users') 
            ->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',])->default('pending');

            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records') ->default(0);
            $table->unsignedInteger('failed_records') ->default(0);
            $table->timestamp('started_at') ->nullable();
            $table->timestamp('completed_at') ->nullable();
            $table->text('error_message') ->nullable();
            $table->string('batch_id') ->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('batch_id');
        });
    }        
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('imports');
    }
};