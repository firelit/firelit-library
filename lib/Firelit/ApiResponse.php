<?PHP

namespace Firelit;

abstract class ApiResponse extends ServerResponse {
	
	protected $response = array();
	protected $responseSent = false;
	
	static $responseType = false;
	static $responseObject;
	
	public function __construct() { 
		parent::__construct();
	}
	
	public function setTemplate($template) {
		$this->response = array_merge($this->response, $template);	
	}
	
	public function set($response) {
		$this->response = array_merge($this->response, $response);	
	}
	
	public function respondAndEnd($response = array()) {
		$this->respond($response, true);
		exit;	
	}
	
	public function responseComplete() {
		
		if ($this->responseSent) return true;
		
		if ($this->code == 204) return true;
			
		return false;
		
	}

	abstract public function respond($response = array(), $end = false) { }
	
	public function mute() {
		$this->responseSent = true;
	}
	
	public function __destruct() {
		if ($this->responseSent) return;
		
		$this->respond();
	}
	
}