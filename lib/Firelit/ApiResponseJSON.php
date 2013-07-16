<?PHP

namespace Firelit;

class ApiResponseJSON extends ApiResponse {
	
	public $jsonCallback = false;
	
	public function __construct() {
		parent::__construct();
		$this->contentType('application/json');
	}
	
	public function setJsonCallback($callback = false) {
		$this->jsonCallback = $callback;
	}
	
	public function respond($response = array(), $end = false) {
		
		$this->set($response);

		if ($this->responseComplete()) exit;
		
		if ($this->jsonCallback) echo $this->jsonCallback .'(';
		
		echo json_encode($this->response);
		
		if ($this->jsonCallback) echo ');';
		
		$this->responseSent = true;
		
		if ($end) exit;
		
	}
	
}