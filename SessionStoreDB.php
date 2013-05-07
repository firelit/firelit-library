<?PHP

namespace 'Firelit';

class SessionStoreDB extends SessionStore {
	
	private $query, $sid, $sessionAvail;
	
	// Defaults
	private $config = array(
		'keyName' => 'firelit-sid', // Name of the cookie stored in remote browser
		'sidLen' => 32, // Length of key in characters
		'defExpSec' => (60 * 60 * 24 * 7) // How long session variables are stored by default (in seconds)
	);
	
	public function __construct(Query $queryObject, $config = array()) {
		
		$this->query = $queryObject;
		
		// Merge config data with defaults
		$this->config = array_merge($config, $this->config);
		
		$keyName = $this->config['keyName'];
		
		$sid = false;
		
		if (isset($_COOKIE[$keyName]) && (strlen($_COOKIE[$keyName]) == $this->config['sidLen'])) {
			// Key available
			
			// Sanitize key
			$sid = $_COOKIE[$keyName];
			$sid = preg_replace('/[^0-9A-Za-z]+/', '', $sid);
			
			// After invalid characters removed, too short?
			if (strlen($sid) != $this->config['sidLen']) $sid = false;
			
		}
		
		if (!$sid) {
			// No key, generate
			
			// Too late if headers sent (throw exception later if access attempted)
			if (headers_sent()) return false;
			
			$sid = self::createSid($this->config['sidLen']);
			
			$expire = time() + ( 86400 * 365 * 10 ); // 10 years from now
			
			// cookie_name, cookie_value, expire_time, path, host, ssl_only, no_js_access
			setcookie($keyName, $sid, $expire, '/', $_SERVER['HTTP_HOST'], false, true);
			
		}
		
		$this->sessionAvail = true;
		$this->sid = $sid;
		
	}
	
	public function set($name, $value, $expires = false) {
		
		if (!$this->sessionAvail)
			throw new \Exception('Session ID could not be set. Session data will be lost.');
			
		$q = clone $this->query;
		
		$q->replace('Sessions', array(
			'sid' => $this->sid,
			'name' => $name,
			'value' => serialize($value),
			'expires' => array('SQL', ($expires ? 'DATE_ADD(NOW(), INTERVAL '. $expires .' SECOND)' : '0000-00-00 00:00:00'))
		));
		
		return $q->success(__FILE__, __LINE__);
		
	}
	
	public function get($name) {
	
		if (!$this->sessionAvail)
			throw new \Exception('Session ID could not be set. Session data not available.');
			
		$q = clone $this->query;
		
		$q->select('Sessions', false, "`sid`=:sid AND `name`=:name AND `expires`>NOW()", array(':sid' => $this->sid, ':name' => $name), 1);
		
		if (!$q->success(__FILE__, __LINE__)) return null;
		
		if ($row = $q->getRow()) return unserialize($row['value']);
		else return null;
		
	}
	
	public function destroy() {
		
		// Nothing to destroy
		if (!$this->sessionAvail) return true;
		
		$q = clone $this->query;
		
		$q->delete('Sessions', "`sid`=:sid", array(':sid' => $this->sid));
		
		$res2 = self::cleanExpired();
		
		return $q->success(__FILE__, __LINE__);
		
	}
	
	static function createSid($len) {
		
	  $symArray = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	  $symArrayLen = strlen($symArray);
	  
	  $key = preg_replace('/[^0-9a-zA-Z]+/', '', base64_encode(hash('sha256', microtime() . $_SERVER['REMOTE_ADDR'] . $_SERVER['REMOTE_PORT'], true))); 
	  $key = substr($key, mt_rand(0, 5), round($len / 2));
	  
	  while (strlen($key) < $len)
	  	$key .= substr($symArray, mt_rand() % $symArrayLen, 1); 
	
		return $key;
		
	}
	
	static function cleanExpired() {
		// Clean out expired data
		
		$q = clone $this->query;
		
		$q->delete('Sessions', "`expires` <= NOW()");
		
		return $q->success(__FILE__, __LINE__);
		
	}
	
	static function install(Query $query) {
		
		// One-time install
		// Create the supporting tables in the db
		
		// TODO - TEST! utf8mb4 may not work....
		$sql = "CREATE TABLE IF NOT EXISTS `Sessions` (
			  `sid` varchar(". $this->config['sidLen'] .") NOT NULL COLLATE utf8mb4_unicode_cs,
			  `name` varchar(32) NOT NULL,
			  `value` longtext NOT NULL,
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `expires` datetime NOT NULL,
			  PRIMARY KEY  (`sid`,`name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;";
			
		$q = $query->query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed! ('. __FILE__ .':'. __LINE__ .')');
			
	}
}