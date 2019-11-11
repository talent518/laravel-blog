<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

/* @var $this Illuminate\Console\Command */

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('db:importable', function () {
	$exportPath = app()->basePath('database/export');
	$this->info(implode(PHP_EOL, array_filter(scandir($exportPath), function($name) {return $name !== '.' && $name !== '..' && $name !== '.gitignore';})));
})->describe('Display all importable directory name(backup name).');
