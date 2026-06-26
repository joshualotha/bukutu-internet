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

uses(Tests\TestCase::class)->in('Feature', 'Unit');
