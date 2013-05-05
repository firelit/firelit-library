<?PHP

namespace 'Firelit';

class Session {
	
	private $store;
	
	private function __construct(SessionStore $store) {	
		
		$this->store = $store;
		
	}
	
	function __set($name, $val, $expireSeconds = false) {
		
		$this->store->set($name, $val, $expireSeconds);
		
	}
	
	function __get($name) {
		
		return $this->store->get($name);
		
	}
	
	public function destroy() {
		// Remove all data from and traces of the current session
		
		$this->store->destroy();
		
	}
	
}
