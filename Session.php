<?PHP

namespace 'Fireit';

class Session {
	public static $instance = false;
	public $SID = false;
	public $data = false;
	public $loggedIn = false;
	public $user = false;
	public $userData = false;
	
	private function __construct() {	}
	
	public static function retrieve() {
		if (self::$instance) return self::$instance;
		
		$class = __CLASS__;
		self::$instance = new Firelit\$class;
		self::$instance->getSessionId();
		return self::$instance;
	}
	
	function getSessionId() {
		if (isset($_REQUEST["sid"]) && (strlen($_REQUEST["sid"]) == SID_KEY_LEN)) {
			// Key sent
			$sid = $_REQUEST["sid"];
			cleanUTF8($sid, false);
			$sid = preg_replace('/[^0-9A-Za-z]+/', '', $sid);
			if (strlen($sid) == SID_KEY_LEN)
				return $this->SID = $sid;
			else {
				// Invalid key length, regenerate
				if (headers_sent()) return false;
				$this->SID = createKey(SID_KEY_LEN);
				setcookie('sid', $this->SID, time() + 86400 * SID_EXPIRE_DAYS, '/', $_SERVER['HTTP_HOST'], SSL_INSTALLED, TRUE);
				return $this->SID;
			}
		} else {
			// No key sent, regenerate
			if (headers_sent()) return false;
			$this->SID = createKey(SID_KEY_LEN);
			setcookie('sid', $this->SID, time() + 86400 * SID_EXPIRE_DAYS, '/', $_SERVER['HTTP_HOST'], SSL_INSTALLED, TRUE);
			return $this->SID;
		}
	}
	
	function setNewKey() {
		if (!headers_sent()) {
			$this->SID = createKey(SID_KEY_LEN);
			setcookie('sid', $this->SID, time() + 86400 * SID_EXPIRE_DAYS, '/', $_SERVER['HTTP_HOST'], SSL_INSTALLED, TRUE);
		} else logIt('. Error setting new key, headers already sent', __FILE__, __LINE__);
	}
	
	function getData($flushCache = false) {
		if ($this->data && !$flushCache) return $this->data;
		
		$sql = "SELECT * FROM `Sessions` WHERE `key` = '". asl($this->SID). "' AND `expires` > NOW()";
		$q = new Firelit\Query($sql);
		
		if ($q->checkError(__FILE__, __LINE__)) {
			$this->data = array();
			
			while ($row = $q->getRow()) {
				$this->data[$row['name']] = unserialize($row['value']);
				if (($row['name'] == '_loggedIn')) $this->loggedIn  = unserialize($row['value']);
				if (($row['name'] == '_user')) $this->user = unserialize($row['value']);
			}
			
			if ($this->loggedIn && $this->user) {
				
				$sql = "SELECT * FROM `Users` WHERE `id`='". intval($this->user) ."' LIMIT 1";
				$q = new Firelit\Query($sql);
				
				if ($q->checkError(__FILE__, __LINE__)) {
					if ($row = $q->getRow()) {
						$this->userData = $row;
					} else {
						$this->loggedIn = false;
						$this->user = false;
					}
				}
				
			} else {
				$this->loggedIn = false;
				$this->user = false;
			}
			
			return $this->data;
		} else
			return false;
	}
	
	function setData($varName, $varValue, $daysExpire = SDB_EXPIRE_DAYS) {
		// VarName truncated to 32 characters by DB
		
		if (is_null($varValue)) {
			$sql = "DELETE FROM `Sessions` WHERE `key`='". asl($this->SID). "' AND `name`='". asl($varName) ."'";
			$q = new Firelit\Query($sql);
			$res = $q->checkError(__FILE__, __LINE__);
			if ($res) unset($this->data[$varName]);
			return $res;	
		}
		
		if (floatval($daysExpire) < 1)
			$expire = round(floatval($daysExpire) * 24) .' HOUR';
		else
			$expire = intval($daysExpire) .' DAY';
		
		$sql = "UPDATE `Sessions` SET `value`='". asl(serialize($varValue)) ."', `expires`=DATE_ADD(NOW(), INTERVAL ". $expire .") WHERE `key`='". asl($this->SID). "' AND `name`='". asl($varName) ."'";
		$q = new Firelit\Query($sql);
		$res = $q->checkError(__FILE__, __LINE__);
		
		if ($q->getAffected() == 0) {
			$sql = "INSERT INTO `Sessions` (`key`, `name`, `value`, `expires`) VALUES ('". asl($this->SID). "', '". asl($varName) ."', '". asl(serialize($varValue)) ."', DATE_ADD(NOW(), INTERVAL ". $expire ."))";
			$q = new Firelit\Query($sql);
			$res = $q->checkError(__FILE__, __LINE__);
		}
		
		if ($res) $this->data[$varName] = $varValue;
		
		return $res;
	}
	
	public function logout() {
		$this->setData('_loggedIn', null);
		$this->setData('_user', null);
		$this->loggedIn = false;
		$this->user = false;
		$this->userData = false;
	}
	
	public function login($user) {
		$user = intval($user);
		if ($user == 0) return;
		
		$this->setNewKey();
		$this->setData('_loggedIn', true);
		$this->setData('_user', $user);
		$this->loggedIn = true;
		$this->user = $user;
		
		// To load $session->userData after calling login():
		// Set $session->data = false;
		// And call $session->getData();
	}
	
	function clearData() {
		$sql = "DELETE FROM `Sessions` WHERE `key`='". asl($this->SID). "'";
		$q1 = new Firelit\Query($sql);
		
		$sql = "DELETE FROM `Sessions` WHERE `expires` <= NOW()";
		$q2 = new Firelit\Query($sql);
	
		return ($q1->checkError(__FILE__, __LINE__) && $q2->checkError(__FILE__, __LINE__));
	}
}
