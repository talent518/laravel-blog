<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseExport extends Command {

	/**
	 * The name and signature of the console command.
	 * 
	 * @var string
	 */
	protected $signature = 'db:export {name? : Exported directory name.} {--format=sql : table data format(sql,dat)}';

	/**
	 * The console command description.
	 * 
	 * @var string
	 */
	protected $description = 'Database export as sql file';

	/**
	 * Create a new command instance.
	 * 
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 * 
	 * @return mixed
	 */
	public function handle() {
		$format = $this->option('format');
		if ($format !== 'sql' && $format !== 'dat') {
			$this->error('format option value error, for example: --format=sql or --format=dat');
			return 1;
		}
		$basePath = app()->basePath('database/export/' . ($this->argument('name') ?? date('YmdHis')));
		is_dir($basePath) or mkdir($basePath, 0755, true);
		
		$pdo = DB::getPdo(); /* @var $pdo \PDO */
		$dbName = env('DB_DATABASE');
		
		$statement = $pdo->prepare('SELECT table_name,table_type FROM information_schema.TABLES WHERE table_schema=?');
		$statement->execute([$dbName]);
		$tables = $statement->fetchAll(\PDO::FETCH_NUM);
		
		foreach ($tables as $table) {
			list ($table, $type) = $table;
			
			if ($type === 'VIEW') {
				echo 'VIEW: ', $table, PHP_EOL;
				$statement = $pdo->prepare('SHOW CREATE VIEW `' . $table . '`');
				$statement->execute();
				$row = $statement->fetch(\PDO::FETCH_ASSOC);
				$this->dropOrCreate($basePath . '/' . $table . '.view', 'VIEW', $table, $row['Create View']);
				continue;
			} else {
				echo 'Table: ', $table;
				
				$statement = $pdo->prepare('SHOW CREATE TABLE `' . $table . '`');
				$statement->execute();
				$row = $statement->fetch(\PDO::FETCH_ASSOC);
				$this->dropOrCreate($basePath . '/' . $table . '.ddl', 'TABLE', $table, $row['Create Table']);
				
				if ($format === 'sql') {
					$statement = $pdo->prepare('SELECT * FROM `' . $table . '`');
					$statement->execute();
					$fp = fopen($basePath . '/' . $table . '.sql', 'w');
					$i = 0;
					echo ', ', $statement->rowCount(), ' rows Data ';
					while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
						if (($i ++) % 100 === 0) {
							if ($i > 1) {
								fwrite($fp, ";\n");
								fflush($fp);
								echo '.';
							}
							fwrite($fp, 'INSERT INTO `');
							fwrite($fp, $table);
							fwrite($fp, '` VALUES');
						} else {
							fwrite($fp, ',');
						}
						fwrite($fp, '(');
						$j = 0;
						foreach ($row as $col) {
							if ($j ++)
								fwrite($fp, ',');
							if ($col === null)
								$col = 'NULL';
							elseif (! is_numeric($col))
								$col = $pdo->quote($col);
							fwrite($fp, $col);
						}
						fwrite($fp, ')');
					}
					if ($i) {
						fwrite($fp, ";\n");
						fflush($fp);
					}
					fclose($fp);
					echo '.', PHP_EOL;
				} else {
					echo ', outfile as dat format', PHP_EOL;
					$datFile = $basePath . '/' . $table . '.dat';
					file_put_contents($basePath . '/' . $table . '.sql', 'LOAD DATA INFILE \'' . $datFile . '\' REPLACE INTO TABLE `' . $table . '` CHARACTER SET utf8 FIELDS TERMINATED BY 0x0f ENCLOSED BY 0x01');
					$pdo->prepare('SELECT * FROM `' . $table . '` INTO OUTFILE \'' . $datFile . '\' CHARACTER SET utf8 FIELDS TERMINATED BY 0x0f ENCLOSED BY 0x01')->execute();
				}
			}
		}
		
		$statement = $pdo->prepare('SELECT `name`,`type` FROM mysql.proc WHERE `db`=?');
		$statement->execute([$dbName]);
		$procs = $statement->fetchAll(\PDO::FETCH_NUM);
		
		foreach ($procs as $proc) {
			list ($name, $type) = $proc;
			echo $type, ': ', $name, PHP_EOL;
			
			$statement = $pdo->prepare('SHOW CREATE ' . $type . ' `' . $name . '`');
			$statement->execute();
			
			$row = $statement->fetch(\PDO::FETCH_ASSOC);
			
			$this->delimiter($basePath . '/' . $name . '.' . substr($_type = strtolower($type), 0, 4), $type, $name, $row['Create ' . ucfirst($_type)]);
		}
		
		$statement = $pdo->prepare('SELECT trigger_name FROM information_schema.TRIGGERS WHERE trigger_schema=?');
		$statement->execute([$dbName]);
		$triggers = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
		foreach ($triggers as $trigger) {
			echo 'Trigger: ', $trigger, PHP_EOL;
			$statement = $pdo->prepare('SHOW CREATE TRIGGER `' . $trigger . '`');
			$statement->execute();
			$row = $statement->fetch(\PDO::FETCH_ASSOC);
			$this->delimiter($basePath . '/' . $trigger . '.trigger', 'TRIGGER', $trigger, $row['SQL Original Statement']);
		}
		
		$statement = $pdo->prepare('SELECT event_name FROM information_schema.EVENTS WHERE event_schema=?');
		$statement->execute([$dbName]);
		$events = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
		foreach ($events as $event) {
			echo 'Event: ', $event, PHP_EOL;
			$statement = $pdo->prepare('SHOW CREATE EVENT `' . $event . '`');
			$statement->execute();
			$row = $statement->fetch(\PDO::FETCH_ASSOC);
			$this->delimiter($basePath . '/' . $event . '.event', 'EVENT', $event, $row['Create Event']);
		}
		
		// 		$statement = $pdo->prepare('CALL getTableNames(@tables)');
		// 		$statement->execute();
		
		// 		$statement = $pdo->prepare('SELECT @tables');
		// 		$statement->execute();
		// 		var_dump($statement->getColumnMeta(0), $statement->fetchColumn(0));
	}

	private function dropOrCreate($file, $type, $name, $sql) {
		$fp = fopen($file, 'w');
		fwrite($fp, 'DROP ');
		fwrite($fp, $type);
		fwrite($fp, ' IF EXISTS `');
		fwrite($fp, $name);
		fwrite($fp, '`;');
		fwrite($fp, PHP_EOL);
		fwrite($fp, $sql);
		fwrite($fp, ';');
		fwrite($fp, PHP_EOL);
	}

	private function delimiter($file, $type, $name, $sql) {
		$fp = fopen($file, 'w');
		fwrite($fp, 'DELIMITER $$');
		fwrite($fp, PHP_EOL);
		fwrite($fp, 'DROP ');
		fwrite($fp, $type);
		fwrite($fp, ' IF EXISTS `');
		fwrite($fp, $name);
		fwrite($fp, '`$$');
		fwrite($fp, PHP_EOL);
		fwrite($fp, $sql);
		fwrite($fp, '$$');
		fwrite($fp, PHP_EOL);
		fwrite($fp, 'DELIMITER ;');
		fwrite($fp, PHP_EOL);
	}
}
