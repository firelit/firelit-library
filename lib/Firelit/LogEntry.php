<?PHP

namespace Firelit;

class LogEntry {
	
	// Default config
	private static $config = array(
		'database' => array(
			'enabled' => false, // Enable database-based logging
			'tablename' => 'Log' // Name of table to log to
		),
		'file' => array(
			'enabled' => false, // Enable file-based logging
			'dbbackup' => false, // Use file-based logging as backup for db failures
			'filename' => '.applog' // File where to log entries
		),
		'levels' => array(
			'min' => 0, // Minimum logging level
			'max' => 5 // Maximum logging level
		)
	);
	
	function __construct($level = false, $entry = false, $file = false, $line = 0, $user = false) { 
		/*
		
			$level : The level of criticality. Suggested scale:
				0 = Debug - for research and debug purposes
				1 = Notice - for auditing purposes
				2 = Important - a log entry that is slightly more important
				3 = Warning - something that may or may not be an error
				4 = Error - something is wrong and needs to be checked soon
				5 = Critical - highest level, serious error, must be addressed ASAP
			$entry : A textual description of the issue *OR* an exception object
			$file : The source file for the error
			$line : The line number of the issue reporting
			$user : The user logged in, if available
			
		*/
		
		// If it is an actual entry, not just instantiating an object, add log entry:
		if ($level != false) $this->now($level, $entry, $file, $line, $user);
		
	}
	
	static function config($config) {
		$this->config = array_merge_recursive($this->config, $config);
	}
	
	public function now($level, $entry, $file = false, $line = 0, $user = false) {
		
		if (is_object($entry) && ($entry instanceof \Exception)) {
			$exception = $entry;
			if (!$file) $file = $exception->getFile();
			if (!$line) $line = $exception->getLine();
			$entry = $exception->getMessage();
			return false;
		}
		
		if (!self::$config['database']['enabled'] && !self::$config['file']['enabled']) 
			throw new \Exception('No logging destination specified.');
		
		$level = intval($level);
		if ($level < self::$config['levels']['min']) $level = self::$config['levels']['min'];
		if ($level > self::$config['levels']['max']) $level = self::$config['levels']['max'];
		
		$dbError = false;
		
		if (self::$config['database']['enabled']) {
			
			$dbError = ! $this->toDb($level, $entry, $file, $line, $user);
			
		}
		
		if (self::$config['file']['enabled'] && ($dbError || !$fileBackupOnly)) {
			
			$this->toFile($level, $entry, $file, $line, $user);
			
		}
		
	}
	
	private noPipe($string) {
		return str_replace('|', ';', $string);
	}
	
	public toDb($level, $entry, $file, $line = 0, $user = false) {
	
		$q = new Query();
		
		$q->insert(self::config['database']['tablename'], array(
			'level' => $level,
			'entry' => $entry,
			'source' => $file .':'. $line,
			'user' => ( $user ? $user : '' ),
			'remoteip' => $_SERVER['REMOTE_ADDR'],
			'context' => serialize(array(
				'agent' => $_SERVER['HTTP_USER_AGENT'],
				'ip' => $_SERVER['REMOTE_ADDR'],
				'host' => $_SERVER['HTTP_HOST'],
				'port' => $_SERVER['SERVER_PORT'],
				'verb' => $_SERVER['REQUEST_METHOD'],
				'url' => $_SERVER['REQUEST_URI'],
				'server' => $_SERVER['SERVER_NAME']
			))
		));
		
		return $q->success();
		
	}
	
	public toFile($level, $entry, $file, $line = 0, $user = false) {
	
		$text = "\n". 
			$this->noPipe($level) .' | '. 
			$this->noPipe($entry) .' | '. 
			$this->noPipe($file .':'. $line) .' | '. 
			$this->noPipe($user) .' | '. 
			$_SERVER['REMOTE_ADDR'] .' | '. 
			date('r');
		
		return file_put_contents(self::$config['file']['filename'], $text, FILE_APPEND);
		
	}
	
	static public install() {
		// One-time install
		// Create the supporting tables in the db
		
		$sql = "CREATE TABLE IF NOT EXISTS `". self::$config['database']['tablename'] ."` (
			  `id` int(10) UNSIGNED NOT NULL auto_increment,
			  `level` int(5) UNSIGNED,
			  `entry` text NOT NULL,
			  `source` text NOT NULL,
			  `user` text NOT NULL,
			  `remoteip` tinytext NOT NULL,
			  `context` text NOT NULL,
			  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  KEY `id` (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;";
			
		$q = new Query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed!');
		
	}
}