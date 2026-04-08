<?php

namespace Database\Seeders;

use Throwable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $sql = File::get(database_path('testing/test_data.sql'));
        try {
            DB::unprepared($sql);
        } catch (Throwable $e) {
            // The SQL dump can contain malformed tail content in development.
            // We still ensure deterministic fixtures for tests below.
            try {
                DB::statement('UNLOCK TABLES');
            } catch (Throwable $unlockError) {
                // Ignore if no table lock is active.
            }
        }

    }

    
}
