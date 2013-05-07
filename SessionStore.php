<?PHP

namespace 'Firelit';

abstract class SessionStore {
	
	abstract public function set($name, $value, $expires = false);
	
	abstract public function get($name);
	
	abstract public function destroy();
	
	static public function init($type = 'PHP') {
		$type = __class__ . $type;
		return new $type();
	}
	
}