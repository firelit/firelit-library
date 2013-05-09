<?PHP

namespace Firelit;

class ApiResponseJSON extends ApiResponse {
	
	public $jsonCallback = false;
	
	public function setCallback($callback = false) {
		$this->jsonCallback = $callback;
	}
	
	public function respond($response = array(), $end = false) {
		
		if (!headers_sent()) header('Content-type: application/json; charset=utf-8');
		
		parent::respond($response, $end);
	
		if ($this->jsonCallback) echo $this->jsonCallback .'(';
		
		echo json_encode($this->response);
		
		if ($this->jsonCallback) echo ');';
		
		$this->responseSent = true;
		
		if ($end) exit;
		
	}
	
}