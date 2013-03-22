<?PHP

namespace 'Firelit';

if (!defined('FIRELIT_SESSION_SID_LEN')) define('FIRELIT_SESSION_SID_LEN', 32); // Length of the session key
if (!defined('FIRELIT_SESSION_DAYS_EXPIRE')) define('FIRELIT_SESSION_DAYS_EXPIRE', 7); // How many days after the session key expires (must be int, use php.ini to enforce with PHP session use)
if (!defined('FIRELIT_SESSION_KEY_NAME')) define('FIRELIT_SESSION_KEY_NAME', 'firelit-sid'); // Name of the session key (eg, cookie name)
if (!defined('FIRELIT_SESSION_USE_DB')) define('FIRELIT_SESSION_USE_DB', false); // Use the database for session management (else use PHP sessions)

include_once('library.php');

class Session {
	
	public $SID = false;
	
	private function __construct() {	
		
		$sid = false;
		
		if (isset($_REQUEST[FIRELIT_SESSION_KEY_NAME]) && (strlen($_REQUEST[FIRELIT_SESSION_KEY_NAME]) == FIRELIT_SESSION_SID_LEN)) {
			// Key available
			
			// Sanitize key
			$sid = $_REQUEST[FIRELIT_SESSION_KEY_NAME];
			cleanUTF8($sid, false);
			$sid = preg_replace('/[^0-9A-Za-z]+/', '', $sid);
			
			if (strlen($sid) != FIRELIT_SESSION_SID_LEN)
				$sid = false;
			
		}
		
		if (!$sid) {
			// No key, generate
			
			if (headers_sent()) return false; // Can't help now
			
			$sid = createKey(FIRELIT_SESSION_SID_LEN);
			
			$expire = time() + ( 86400 * FIRELIT_SESSION_DAYS_EXPIRE );
			
			// cookie_name, cookie_value, expire_time, path, host, ssl_only, no_js_access
			setcookie(FIRELIT_SESSION_KEY_NAME, $sid, $expire, '/', $_SERVER['HTTP_HOST'], SSL_INSTALLED, TRUE);
			
		}
		
		if (!FIRELIT_SESSION_USE_DB) {
			// Use PHP's session handling
			
			session_id($sid);
			session_start();
			
		}
		
		return $this->SID = $sid;
		
	}
	
	function __get($name) {
		
		if (FIRELIT_SESSION_USE_DB) {
			
			$q = new Query();
			$q->select('Sessions', "`sid`='". Query:asl($this->SID) ."' AND `name`='". Query::asl($name) ."' AND `expires`>NOW()", 1);
			
			if (!$q->success(__FILE__, __LINE__)) return null;
			
			if ($row = $q->getRow()) return unserialize($row['value']);
			else return null;
			
		} else {
			
			return $_SESSION[$name];
			
		}
		
	}
	
	function __set($name, $val) {
		
		if (FIRELIT_SESSION_USE_DB) {
			
			$q = new Query();
			$q->replace('Sessions', array(
				'sid' => $this->SID,
				'name' => $name,
				'value' => serialize($val),
				'expires' => array('SQL', 'DATE_ADD(NOW(), INTERVAL '. FIRELIT_SESSION_DAYS_EXPIRE .' DAY)')
			));
			
			return $q->success(__FILE__, __LINE__);
			
		} else {
			
			$_SESSION[$name] = $val;
			
			return true;
			
		}
		
	}
	
	public function destory() {
		// Remove all data from and traces of the current session
		
		if (FIRELIT_SESSION_USE_DB) {
			
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
		if (!FIRELIT_SESSION_USE_DB) return false;
		
		$q = new Query();
		$q->delete('Sessions', "`expires` <= NOW()");
		
		return $q->success(__FILE__, __LINE__);
		
	}
	
	static public install() {
		// One-time install
		// Create the supporting tables in the db
		
		// Nothing to install!
		if (!FIRELIT_SESSION_USE_DB) return false;
		
		$sql = "CREATE TABLE IF NOT EXISTS `Sessions` (
			  `sid` varchar(". FIRELIT_SESSION_SID_LEN .") NOT NULL,
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
