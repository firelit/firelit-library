<?PHP

namespace 'Firelit';

include_once('library.php');

class ApiResponse {
	
	private $response = array();
	private $responseSent = false;
	private $responseType = 'JSON';
	public $jsonCallback = '';
	public $xmlNameSpace = '';
	public $xmlRootNode = '';
	
	public function __construct($responseType = 'JSON', $template = array(), $xmlRootNode = 'response', $xmlNameSpace = '') {
		$this->responseType = upper($responseType);
		$this->response = $template;
		$this->xmlNameSpace = $xmlNameSpace;
		$this->xmlRootNode = $xmlRootNode;
	}
	
	public function set($response) {
		$this->response = array_merge($this->response, $response);	
	}
	
	public function respond($response = array(), $end = false) {
		if ($this->responseSent) 
			if ($end) exit;
			else return;
		
		$this->set($response);
		
		if ($this->responseType == 'JSON') {
			
			if (!headers_sent()) header('Content-type: application/json; charset=utf-8');
			
			if (strlen($this->jsonCallback)) echo $this->jsonCallback .'(';
			
			echo json_encode($this->response);
			
			if (strlen($this->jsonCallback)) echo ');';
			
			$this->responseSent = true;
			
		} elseif ($this->responseType == 'XML') {
			
			if (!headers_sent()) header('Content-type: text/xml; charset=utf-8');
			
			$xml = new SimpleXMLElement('<response/>', 0, false, $this->xmlNameSpace);
			
			foreach ($this->response as $thisName => $thisVal) {
				$this->arrayToXML($xml, $thisName, $thisVal);
			}
			
			echo $xml->asXML();
			
			$this->responseSent = true;
			
		} else 
			throw new Exception('Invalid response type.');
		
		if ($end) exit;
	}
	
	private function arrayToXML(&$xml, $name, $val) {
		if (is_array($val)) {
			
			$xmlChild = $xml->addChild($name);
			
			foreach ($val as $thisName => $thisVal) {
				$this->arrayToXML($xmlChild, $thisName, $thisVal);
			}
			
		} elseif (is_bool($val)) {
			$xml->addChild($name, ($val ? 'true' : 'false'));
		} else
			$xml->addChild($name, $val);
	}
		
	public function mute() {
		$this->responseSent = true;
	}
	
	public function __destruct() {
		if ($this->responseSent) return;
		
		$this->respond();
	}
	
}