<?PHP

namespace 'Firelit';

abstract class SessionStore {
	
	abstract public function set($name, $value, $expires = false);
	
	abstract public function get($name);
	
	abstract public function destroy();
	
	static function init($type = 'PHP') {
		$type = 'SessionStore'. $type;
		return new $type();
	}
	
}