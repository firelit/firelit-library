<?PHP

namespace 'Firelit';

include_once('library.php');

class Session {
	
	public $SID = false;
	
	// Default values
	public $config = array(
		'KEY_NAME' => 'firelit-sid', // Name of the cookie stored in remote browser
		'SID_LEN' => 32, // Length of key in charchters
		'DAYS_EXPIRE' => 7, // How long session variables are stored (n/a if USE_DB is false)
		'USE_DB' => false // Instead of native PHP session support, for multi-server environment
	);
	
	private function __construct($config = false) {	
		
		if ($config)
			$this->config = array_merge($this->config, $config);
			
		$sid = false;
		
		if (isset($_REQUEST[$this->config['KEY_NAME']]) && (strlen($_REQUEST[$this->config['KEY_NAME']]) == $this->config['SID_LEN'])) {
			// Key available
			
			// Sanitize key
			$sid = $_REQUEST[$this->config['KEY_NAME']];
			cleanUTF8($sid, false);
			$sid = preg_replace('/[^0-9A-Za-z]+/', '', $sid);
			
			if (strlen($sid) != $this->config['SID_LEN'])
				$sid = false;
			
		}
		
		if (!$sid) {
			// No key, generate
			
			if (headers_sent()) return false; // Can't help now
			
			$sid = createKey($this->config['SID_LEN']);
			
			$expire = time() + ( 86400 * 365 * 10 ); // 10 years from now
			
			// cookie_name, cookie_value, expire_time, path, host, ssl_only, no_js_access
			setcookie($this->config['KEY_NAME'], $sid, $expire, '/', $_SERVER['HTTP_HOST'], SSL_INSTALLED, TRUE);
			
		}
		
		if (!$this->config['USE_DB']) {
			// Use PHP's session handling
			
			session_id($sid);
			session_start();
			
		}
		
		return $this->SID = $sid;
		
	}
	
	function __get($name) {
		
		if ($this->config['USE_DB']) {
			
			$q = new Query();
			$q->select('Sessions', "`sid`='". Query:asl($this->SID) ."' AND `name`='". Query::asl($name) ."' AND `expires`>NOW()", 1);
			
			if (!$q->success(__FILE__, __LINE__)) return null;
			
			if ($row = $q->getRow()) return unserialize($row['value']);
			else return null;
			
		} else {
			
			return $_SESSION[$name];
			
		}
		
	}
	
	function __set($name, $val, $expireDays = false) {
		
		if ($this->config['USE_DB']) {
			
			if (!$expireDays) $expireDays = $this->config['DAYS_EXPIRE'];
			
			$q = new Query();
			$q->replace('Sessions', array(
				'sid' => $this->SID,
				'name' => $name,
				'value' => serialize($val),
				'expires' => array('SQL', 'DATE_ADD(NOW(), INTERVAL '. $expireDays .' DAY)')
			));
			
			return $q->success(__FILE__, __LINE__);
			
		} else {
			
			$_SESSION[$name] = $val;
			
			return true;
			
		}
		
	}
	
	public function destory() {
		// Remove all data from and traces of the current session
		
		if ($this->config['USE_DB']) {
			
			$q = new Query();
			$q->delete('Sessions', "`sid`='". asl($this->SID). "'");
			
			$res2 = self::clean();
			
			return ($q->success(__FILE__, __LINE__) && $res2);
			
		} else {
			
			return session_destroy();
			
		}
		
	}
	
	static function cleanExpired() {
		// Clean out expired data
		
		// Nothing to clean!
		if (!$this->config['USE_DB']) return false;
		
		$q = new Query();
		$q->delete('Sessions', "`expires` <= NOW()");
		
		return $q->success(__FILE__, __LINE__);
		
	}
	
	static public install() {
		// One-time install
		// Create the supporting tables in the db
		
		// Nothing to install!
		if (!$this->config['USE_DB']) return false;
		
		$sql = "CREATE TABLE IF NOT EXISTS `Sessions` (
			  `sid` varchar(". $this->config['SID_LEN'] .") NOT NULL,
			  `name` varchar(32) NOT NULL,
			  `value` longtext NOT NULL,
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `expires` datetime NOT NULL,
			  PRIMARY KEY  (`sid`,`name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;";
			
		$q = new Query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed!');
			
	}
}
