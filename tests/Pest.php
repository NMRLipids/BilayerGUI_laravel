<?php

use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

pest()->beforeEach(function () {
	// A failure to prepare the database (migrate or seed) is not a normal test
	// failure: it affects every test and produces huge, repetitive output.
	// Abort the entire run immediately with a concise message instead.
	try {
		$migrateCode = Artisan::call('migrate:fresh', ['--force' => true]);
		if ($migrateCode !== 0) {
			throw new \RuntimeException('migrate:fresh failed during test bootstrap');
		}

		$seedCode = Artisan::call('db:seed', [
			'--class' => TestDatabaseSeeder::class,
			'--force' => true,
		]);
		if ($seedCode !== 0) {
			throw new \RuntimeException('TestDatabaseSeeder failed during test bootstrap');
		}
	} catch (\Throwable $e) {
		fwrite(STDERR, PHP_EOL . 'Database preparation failed, aborting test run:' . PHP_EOL);
		fwrite(STDERR, $e->getMessage() . PHP_EOL);
		exit(1);
	}
})->in('Feature');

