<?PHP

namespace Firelit;

class SessionStoreMem extends SessionStore {
	
	private $data = array(), $expires = 0;
	
	public function __construct() { }
	
	public function store($valueArray, $expireSeconds = false) {
		
		if ($expireSeconds) 
			$this->expires = $expireSeconds;
		
		foreach ($valueArray as $key => $val) {
			if (is_null($val)) 
				unset($valueArray[$key]);
		}
		
		$this->data = $valueArray;
		
		return;
		
	}
	
	public function fetch() {
		
		return $this->data;
		
	}
	
	public function destroy() {
		
		$this->data = array();
		return true;
		
	}
	
}