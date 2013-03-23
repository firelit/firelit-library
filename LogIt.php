<?PHP

namespace 'Firelit';

if (!defined('FIRELIT_LOGIT_DB_TABLE')) define('FIRELIT_LOGIT_DB_TABLE', false); // Name of the DB table to log to
if (!defined('FIRELIT_LOGIT_FILE')) define('FIRELIT_LOGIT_FILE', false); // Name of the file to log to
if (!defined('FIRELIT_LOGIT_FILE_BACKUP')) define('FIRELIT_LOGIT_FILE_BACKUP', false); // Use file logging only as backup, only
if (!defined('FIRELIT_LOGIT_LEVEL_MIN')) define('FIRELIT_LOGIT_LEVEL_MIN', 0); // Min allowable priority level
if (!defined('FIRELIT_LOGIT_LEVEL_MAX')) define('FIRELIT_LOGIT_LEVEL_MAX', 5); // Max allowable priority level

include_once('library.php');

class LogIt {
	
	// The name of the table to log to, or false n/a
	public $dbTable = FIRELIT_LOGIT_DB_TABLE;
	// The name of the file to log to, or false n/a
	public $file = FIRELIT_LOGIT_FILE;
	// Write to file only upon DB error
	public $fileBackupOnly = FIRELIT_LOGIT_FILE_BACKUP;
	
	private $minLevel = FIRELIT_LOGIT_LEVEL_MIN;
	private $maxLevel = FIRELIT_LOGIT_LEVEL_MAX;
	
	function __construct($level = false, $entry = false, $file = false, $line = 0, $user = false) { 
		/*
		
			$level : The level of criticality. Suggested scale:
				0 = Debug - for research and debug purposes
				1 = Notice - for auditing purposes
				2 = Important - a log entry that is slightly more important
				3 = Warning - something that may or may not be an error
				4 = Error - something is wrong and needs to be checked soon
				5 = Critical - highest level, serious error, must be addressed ASAP
			$entry : A textual description of the issue
			$file : The source file for the error
			$line : The line number of the issue reporting
			$user : The user logged in, if available
			
		*/
		
		if ($level != false) $this->now($level, $entry, $source, $user);
		
	}
	
	public function now($level, $entry, $file, $line = 0, $user = false) {
		
		if (!$this->dbTable && !$this->file) 
			throw new \Exception('No log destination specified.');
		
		$level = intval($level);
		if ($level < $this->minLevel) $level = $minLevel;
		if ($level > $this->maxLevel) $level = $maxLevel;
		
		$dbError = false;
		
		if ($this->dbTable) {
			
			$dbError = ! $this->toDb($level, $entry, $file, $line, $user);
			
		}
		
		if ($this->file && ($dbError || !$fileBackupOnly)) {
			
			$this->toFile($level, $entry, $file, $line, $user);
			
		}
		
	}
	
	private noPipe($string) {
		return str_replace('|', ';', $string);
	}
	
	public toDb($level, $entry, $file, $line = 0, $user = false) {
	
		$q = new Query();
		$q->insert($this->dbTable, array(
			'level' => $level,
			'entry' => $entry,
			'source' => $file .':'. $line,
			'user' => ( $user ? $user : '' ),
			'remoteip' => $_SERVER['REMOTE_ADDR']
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
		
		return file_put_contents($this->file, $text, FILE_APPEND);
		
	}
	
	static public install() {
		// One-time install
		// Create the supporting tables in the db
		
		if (!FIRELIT_LOGIT_DB_TABLE) 
			throw new \Exception('Table must be defined for LogIt install.');
		
		$sql = "CREATE TABLE IF NOT EXISTS `Log` (
			  `id` int(10) UNSIGNED NOT NULL auto_increment,
			  `level` int(5) UNSIGNED,
			  `entry` text NOT NULL,
			  `source` text NOT NULL,
			  `user` text NOT NULL,
			  `remoteip` tinytext NOT NULL,
			  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  KEY `id` (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
			
		$q = new Query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed!');
		
	}
}