<?PHP

namespace 'Fireit';

class User {
		
	function __construct() { }
	
	function getMeta($uid, $name) {
		
		$sql = "SELECT `value` FROM `UserMeta` WHERE `user`='". asl($uid). "' AND `name`='". asl($name). "' LIMIT 1";
		$q = new Firelit\Query($sql);
		if (!$q->checkError(__FILE__, __LINE__)) return false; // There was an error
		
		if ($q->getRes()) {
			if ($row = $q->getRow())
				return unserialize($row['value']);
			else
				return false;
		} else
			return false;
	}
	
	function setMeta($uid, $name, $value) {
				
		if (is_null($value)) {
			$sql = "DELETE FROM `UserMeta` WHERE `user`='". asl($uid). "' AND `name`='". asl($name) ."'";
			$q = new Firelit\Query($sql);
			return $q->checkError(__FILE__, __LINE__);	
		}
	
		$curVal = self::getMeta($uid, $name);
		
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
	
	function incMeta($uid, $name, $amt = 1) {
		$i = 0;
		
		while (true) {
			$sql = "SELECT `value` FROM `UserMeta` WHERE `user`='". asl($uid). "' AND `name`='". asl($name). "' LIMIT 1";
			$q = new Firelit\Query($sql);
			if (!$q->checkError(__FILE__, __LINE__)) return false; // There was an error
			
			if (!$row = $q->getRow()) { // Doesn't exist
				self::setMeta($uid, $name, $amt); 
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
	
}
