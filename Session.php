<?PHP

namespace 'Firelit';

class Session {
	
	private $store, $cache = array();
	public $sessionCacheEnabled = true;
	
	private function __construct(SessionStore $store) {	
		// Create a session using the given SessionStore object
		
		$this->store = $store;
		
	}
	
	function __set($name, $val, $expireSeconds = false) {
		// Magic sesion value setter 
		
		$res = $this->store->set($name, $val, $expireSeconds);
		
		if ($this->sessionCacheEnabled && $res) $this->cache[$name] = $val;
		
	}
	
	function __get($name, $flushCache = false) {
		// Magic sesion value getter 
		
		if ($this->sessionCacheEnabled && !$flushCache && isset($this->cache[$name]))
			return $this->cache[$name];
		
		$val = $this->store->get($name);
		
		if ($this->sessionCacheEnabled) $this->cache[$name] = $val;
		
		return $val;
		
	}
	
	public function destroy() {
		// Remove all data from and traces of the current session
		
		$this->store->destroy();
		
		$this->cache = array();
		
	}
	
}
