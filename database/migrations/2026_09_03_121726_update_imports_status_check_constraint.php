<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            ALTER TABLE imports
            DROP CONSTRAINT IF EXISTS imports_status_check
        ');

        DB::statement("
            ALTER TABLE imports
            ADD CONSTRAINT imports_status_check
            CHECK (status IN (
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
            ALTER TABLE imports
            DROP CONSTRAINT IF EXISTS imports_status_check
        ');

        DB::statement("
            ALTER TABLE imports
            ADD CONSTRAINT imports_status_check
            CHECK (status IN (
                'pending',
                'processing',
                'completed',
                'failed'
            ))
        ");
    }
};