<?PHP

namespace Firelit;

class ServerRequest {
	
	// All properties accessible via magic getter method
	private $host, $path, $method, $secure, $referer, $cli;
	private $post, $get, $cookie;

	public function __construct($filterAndUnset = true) { 
		
		$this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
		$this->path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : false;
		$this->secure = isset($_SERVER['HTTPS']) ? ($_SERVER['HTTPS'] == 'on') : false;
		$this->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;
		$this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
		$this->cli = (php_sapi_name() == 'cli');
		
		$this->post = $_POST;
		$this->get = $_GET;
		$this->cookie = $_COOKIE;
		
		if ($filterAndUnset) {
			// Filter local copies of POST, GET & COOKIE data
			// Unset global versions to prevent access to un-filtered
			$this->filterInputs();
			
			unset($_POST);
			unset($_GET);
			unset($_COOKIE);
			
		}
	}
	
	public function filterInputs($cleaner = 'Strings::cleanUTF8') {
		
		$cleaner($this->post);
		$cleaner($this->get);
		$cleaner($this->cookie);
	
	}
	
	public function __get($name) {
	
		if (isset($this->$name)) return $this->$name;
		
		throw new \Exception('Invalid property specified.');
		
	}

}