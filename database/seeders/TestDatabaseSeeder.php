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
            // Make sure a dangling table lock from the dump doesn't leak into
            // the rest of the test run, but don't hide the original failure.
            try {
                DB::statement('UNLOCK TABLES');
            } catch (Throwable $unlockError) {
                // Ignore if no table lock is active.
            }

            // PDO appends the full failing SQL (the entire dump) to its message.
            // Strip it so the failure stays readable instead of dumping the file.
            $reason = preg_replace('/\s*\(Connection:.*$/s', '', $e->getMessage());

            throw new \RuntimeException(
                'Failed to load test fixtures from '
                . database_path('testing/test_data.sql')
                . '. This usually means the SQL dump is out of sync with the '
                . 'current schema (e.g. a column was added or removed). '
                . 'Original error: ' . $reason,
                0,
                $e
            );
        }
    }

    
}
