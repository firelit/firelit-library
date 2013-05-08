<?PHP

namespace 'Firelit';

abstract class ApiResponse extends InitExtendable {
	
	private $code = 200;
	private $response = array();
	private $responseSent = false;
	
	static $responseType = false;
	static $responseObject;
	
	public function __construct() { }
	
	public function setTemplate($template) {
		$this->response = array_merge($this->response, $template);	
	}
	
	public function set($response) {
		$this->response = array_merge($this->response, $response);	
	}
	
	public function code($code) {
		$this->code = (int) $code;	
	}
	
	public function respondAndEnd($response = array()) {
		$this->respond($response, true);
		exit;	
	}
	
	public function respond($response = array(), $end = false) {
		
		if ($this->responseSent) 
			if ($end) exit;
			else return;
		
		http_response_code($this->code);
		
		if ($this->code == 204)
			if ($end) exit;
			else return;
			
		$this->set($response);
		
	}
	
	public function mute() {
		$this->responseSent = true;
	}
	
	public function __destruct() {
		if ($this->responseSent) return;
		
		$this->respond();
	}
	
}