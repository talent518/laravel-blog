<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import {name : Backup of directory name.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database import from sql file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    	$exportPath = app()->basePath('database/export');
    	$name = $this->argument('name');
    	$basePath = $exportPath . '/' . $name;
    	if(!$name || !is_dir($basePath)) {
    		if($name) $this->error('The ' . $basePath . ' directory is not exists.');
    		$this->warn('For example: ' . implode(', ', array_filter(scandir($exportPath), function($name) {return $name !== '.' && $name !== '..' && $name !== '.gitignore';})));
    		return 1;
    	}
    	
    	$files = array_filter(scandir($basePath), function($name) {return $name !== '.' && $name !== '..';});
    	
    	$sorts = [];
    	$types = ['ddl','sql','view','trigger','func','proc','event'];
    	foreach ($files as $file) {
    		$sorts[$file] = array_search(pathinfo($file, PATHINFO_EXTENSION), $types);
    	}
    	asort($sorts, SORT_NUMERIC);
    	
    	foreach($sorts as $file=>$n) {
    		if($n !== false) echo 'source ', $basePath, '/', $file, ';', PHP_EOL;
    	}
    }
}
