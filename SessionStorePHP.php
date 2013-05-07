<?PHP

namespace 'Firelit';

class SessionStorePHP extends SessionStore {
	
	public function __construct() {
		
		session_start();
		
	}
	
	public function store($valueArray, $expireSeconds = false) {
		// $expireSeconds ignored - session expired handled by php.ini
		
		foreach ($valueArray as $key => $val) {
			if (is_null($val)) {
				unset($_SESSION[$key]);
				unset($valueArray[$key]);
			}
		}
		
		$_SESSION = $valueArray;
		
		return;
		
	}
	
	public function fetch() {
		
		return $_SESSION;
		
	}
	
	public function destroy() {
		
		return session_destroy();
		
	}
	
}