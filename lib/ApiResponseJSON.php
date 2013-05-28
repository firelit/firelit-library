<?PHP

namespace Firelit;

class ApiResponseJSON extends ApiResponse {
	
	public $jsonCallback = false;
	
	public function __construct() {
		parent::__construct();
		$this->contentType('application/json');
	}
	
	public function setCallback($callback = false) {
		$this->jsonCallback = $callback;
	}
	
	public function respond($response = array(), $end = false) {
		
		parent::respond($response, $end);
	
		if ($this->jsonCallback) echo $this->jsonCallback .'(';
		
		echo json_encode($this->response);
		
		if ($this->jsonCallback) echo ');';
		
		$this->responseSent = true;
		
		if ($end) exit;
		
	}
	
}