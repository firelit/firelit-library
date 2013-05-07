<?PHP

/*

$sess = new Session(); // Session management
$user = new User(); // The User model -- used for any user, not just logged in visitors
$us = new UserSession($sess, $user); // ?????????

*/

namespace 'Firelit';

class User {
	
	private $session;
	public $userId = false;
	public $userData;
		
	function __construct(&$session = false) { 
		$this->session = $session;
		
		if ($this->session->loggedIn) {
			$this->userId = $this->session->userId;
			$this->loadUser();
		}
	}
	
	public function checkLogin($user, $password) {
		
	}
	
	public function login($user) {
		$user = intval($user);
		if ($user == 0) return;
		
		$this->session->loggedIn = true;
		$this->session->userId = $this->userId = $user;
		
		$this->loadUser();
	}
	
	public function logout() {
		$this->session->loggedIn = null;
		$this->session->userId = null;
	}
	
	private function loadUser() {
		
		if (!$this->userId) return false;
			
		$db = new DB();
		
		$db->query("SELECT * FROM `Users` WHERE `id`=:id", array('id' => $this->userId));
		
		if ($db->success(__FILE__, __LINE__)) {
			$this->userData = $db->getRow();
		}
		
	}
	
	public function getMeta($name) {
		
		$db = new DB();
		
		$db->query("SELECT `value` FROM `UserMeta` WHERE `user`=:user AND `name`=:name LIMIT 1", array('user' => $this->userId, 'name' => $name));
		
		if (!$db->success(__FILE__, __LINE__)) 
			throw new \Exception('Error retrieving user meta value from database.'); // There was an error
		
		if ($row = $db->getRow())
			return unserialize($row['value']);
		else
			return null;
			
	}
	
	public function setMeta($name, $value) {
		// TODO
		if (is_null($value)) {
			$sql = "DELETE FROM `UserMeta` WHERE `user`='". asl($this->userId). "' AND `name`='". asl($name) ."'";
			$q = new Firelit\Query($sql);
			return $q->checkError(__FILE__, __LINE__);	
		}
	
		$curVal = $this->getMeta($name);
		
		if ($curVal === $value) {
			
			// Value is already correct!
			return true;
			
		} elseif ($curVal === false) {
			
			// Nothing currently stored
			$sql = "INSERT INTO `UserMeta` (`user`, `name`, `value`) VALUES ('". asl($uid) ."', '". asl($name) ."', '". asl(serialize($value)) ."')";
			$q = new Firelit\Query($sql);
			$res = $q->checkError(__FILE__, __LINE__);
			
			if (!$res) {
				// Somebody beat us to it? Or is the value literaly false?
				$sql = "UPDATE `UserMeta` SET `value`='". asl(serialize($value)) ."' WHERE `user`='". asl($uid). "' AND `name`='". asl($name) ."' LIMIT 1";
				$q = new Firelit\Query($sql);
				$res = $q->checkError(__FILE__, __LINE__);
			}
			
		} else {
			
			// Something there, just needs updating
			$sql = "UPDATE `UserMeta` SET `value`='". asl(serialize($value)) ."' WHERE `user`='". asl($uid). "' AND `name`='". asl($name) ."' LIMIT 1";
			$q = new Firelit\Query($sql);
			$res = $q->checkError(__FILE__, __LINE__);
		
		}
	
		return $res;
		
	}
	
	public function incMeta($name, $amt = 1) {
		$i = 0;
		
		while (true) {
			$sql = "SELECT `value` FROM `UserMeta` WHERE `user`='". asl($uid). "' AND `name`='". asl($name). "' LIMIT 1";
			$q = new Firelit\Query($sql);
			if (!$q->checkError(__FILE__, __LINE__)) return false; // There was an error
			
			if (!$row = $q->getRow()) { // Doesn't exist
				$this->setMeta($name, $amt); 
				return $amt;
			}
			
			$curNum = unserialize($row['value']);
			
			$sql = "UPDATE `UserMeta` SET `value`='". asl(serialize($amt + $curNum)) ."' WHERE `value`='". asl($row['value']) ."' AND `user`='". asl($uid). "' AND `name`='". asl($name). "' LIMIT 1";
			$q = new Firelit\Query($sql);
			if (!$q->checkError(__FILE__, __LINE__)) return false; // There was an error
		
			if ($q->getAffected()) return ($amt + $curNum); // Success!
			
			if ($i++ > 100) {
				logIt('. Lost an increment ('.$uid.', '.$name.', '.$amt.')', __FILE__, __LINE__);
				return false; // There were too many tries
			}
		}
		
	}
	
	static public install() {
		// One-time install
		// Create the supporting tables in the db
		
		$sql = "CREATE TABLE IF NOT EXISTS `Users` (
			  `id` int(10) UNSIGNED NOT NULL auto_increment,
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  KEY `id` (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
			
		$q = new Query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed!');
		
		$sql = "CREATE TABLE IF NOT EXISTS `UserMeta` (
			  `user` int(10) UNSIGNED NOT NULL,
			  KEY `user` (`user`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
			
		$q = new Query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed!');
		
	}
}
