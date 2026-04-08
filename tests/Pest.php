<?php

use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

pest()->beforeEach(function () {
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
})->in('Feature');
