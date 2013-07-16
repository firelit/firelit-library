<?PHP

namespace Firelit;

class ApiResponseXML extends ApiResponse {
	
	private $xmlNameSpace = '';
	private $xmlRootNode = '';
	
	public function __construct() {
		parent::__construct();
		$this->contentType('text/xml');
	}
	
	public function setup($xmlRootNode = 'response', $xmlNameSpace = '') {
		$this->xmlNameSpace = $xmlNameSpace;
		$this->xmlRootNode = $xmlRootNode;
	}
	
	public function respond($response = array(), $end = false) {
		
		$this->set($response);

		if ($this->responseComplete()) exit;
		
		$xml = new \SimpleXMLElement('<response/>', 0, false, $this->xmlNameSpace);
		
		foreach ($this->response as $thisName => $thisVal) {
			$this->arrayToXML($xml, $thisName, $thisVal);
		}
		
		echo $xml->asXML();
		
		$this->responseSent = true;
		
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
	
}