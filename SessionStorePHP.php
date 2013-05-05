<?PHP

namespace 'Firelit';

class SessionStorePHP extends SessionStore {
	
	public function __construct() {
		
		session_start();
		
	}
	
	public function set($name, $value, $expires = false) {
		
		if (is_null($value)) unset($_SESSION[$name]);
		else $_SESSION[$name] = $value;
		
		return true;
		
	}
	
	public function get($name) {
		
		return $_SESSION[$name];
		
	}
	
	public function destroy() {
		
		return session_destroy();
		
	}
	
}