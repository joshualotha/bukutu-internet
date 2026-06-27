<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "Tests\TestCase".
|
*/

use Illuminate\Foundation\Testing\DatabaseMigrations;

// All Feature tests need the database (migrate in-memory SQLite before each test)
uses(Tests\TestCase::class, DatabaseMigrations::class)->in('Feature');

// Unit tests use mocked HTTP, only OrderServiceTest needs the database
uses(Tests\TestCase::class)->in('Unit');
