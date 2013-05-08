<?PHP

namespace 'Firelit';

abstract class Vars extends InitExtendable {
	
	public static $config = array(
		'tableName' => 'Vars'
	);
	
	function __construct() { }
	
	static function get($name, $acct = 0) {	
		
		$sql = "SELECT `value` FROM `Vars` WHERE `account`='". asl($acct) ."' AND `name`='". asl($name). "' LIMIT 1";
		$q = new Firelit\Query($sql);
	
		if ($q->checkError(__FILE__, __LINE__)) {
			if ($row = $q->getRow())
				return unserialize($row['value']);
			else
				return false;
		} else
			return false;
			
	}
	
	static function set($name, $value, $acct = 0) {
		
		if (is_null($value)) {
			$sql = "DELETE FROM `Vars` WHERE `account`='". asl($acct) ."' AND `name`='". asl($name) ."'";
			$q = new Firelit\Query($sql);
			return $q->checkError(__FILE__, __LINE__);	
		}
	
		$curVal = self::get($name, $acct);
		
		if ($curVal === $value) {
			
			// Value is already correct!
			return true;
			
		} elseif ($curVal === false) {
			
			// Nothing currently stored
			$sql = "INSERT INTO `Vars` (`account`, `name`, `value`) VALUES ('". asl($acct) ."', '". asl($name) ."', '". asl(serialize($value)) ."')";
			$q = new Firelit\Query($sql);
			$res = $q->checkError(__FILE__, __LINE__);
			
			if (!$res) {
				// Somebody beat us to it? Or is the value literaly false?
				$sql = "UPDATE `Vars` SET `value`='". asl(serialize($value)) ."' WHERE `account`='". asl($acct) ."' AND `name`='". asl($name) ."' LIMIT 1";
				$q = new Firelit\Query($sql);
				$res = $q->checkError(__FILE__, __LINE__);
			}
			
		} else {
			
			// Something there, just needs updating
			$sql = "UPDATE `Vars` SET `value`='". asl(serialize($value)) ."' WHERE `account`='". asl($acct) ."' AND `name`='". asl($name) ."' LIMIT 1";
			$q = new Firelit\Query($sql);
			$res = $q->checkError(__FILE__, __LINE__);
		
		}
	
		return $res;
		
	}
	
	static function inc($name, $amt = 1, $acct = 0) {
		
		$i = 0;
		
		while (true) {
			$sql = "SELECT `value` FROM `Vars` WHERE `account`='". asl($acct) ."' AND `name`='". asl($name). "' LIMIT 1";
			$q = new Firelit\Query($sql);
			if (!$q->checkError(__FILE__, __LINE__)) return false; // There was an error
			
			if (!$row = $q->getRow()) { // Doesn't exist
				self::set($name, $amt, $acct); 
				return $amt;
			}
			
			$curNum = unserialize($row['value']);
			
			$sql = "UPDATE `Vars` SET `value`='". asl(serialize($amt + $curNum)) ."' WHERE `account`='". asl($acct) ."' AND `value`='". asl($row['value']) ."' AND `name`='". asl($name). "' LIMIT 1";
			$q = new Firelit\Query($sql);
			if (!$q->checkError(__FILE__, __LINE__)) return false; // There was an error
			
			if ($q->getAffected()) return ($amt + $curNum); // Success!
			
			if ($i++ > 100) {
				logIt('. Lost an increment ('.$name.', '.$acct.', '.$amt.')', __FILE__, __LINE__);
				return false; // There were too many tries
			}
		}
		
	}
	
	public static function install() {
		// One-time install
		// Create the supporting tables in the db
		
		// Running MySql >= 5.5.3 ? Use utf8mb4 insetad of utf8.
		$sql = "CREATE TABLE IF NOT EXISTS `". self::$config['tableName'] ."` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `to` text NOT NULL,
			  `subject` text NOT NULL,
			  `email` longtext NOT NULL,
			  `status` enum('QUEUED','SENDING','SENT','ERROR') NOT NULL,
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `expires` datetime NOT NULL,
			  `sent` datetime NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY (`status`),
			  KEY (`expires`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;"
			
		$q = $query->query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed! ('. __FILE__ .':'. __LINE__ .')');
			
		return $query->insertId();
		
	}

}