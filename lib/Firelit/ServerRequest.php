<?PHP

namespace Firelit;

class ServerRequest {
	
	// All properties accessible via magic getter method
	private $host, $path, $method, $secure, $referer, $cli, $headers;
	private $post, $get, $cookie;

	public function __construct($filter = false) { 
		
		$this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
		$this->path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : false;
		$this->secure = isset($_SERVER['HTTPS']) ? ($_SERVER['HTTPS'] == 'on') : false;
		$this->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;
		$this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
		$this->cli = (php_sapi_name() == 'cli');
		
		$this->headers = apache_request_headers();
		
		$this->post = $_POST;
		$this->get = $_GET;
		$this->cookie = $_COOKIE;
		
		if ($filter) {
			// Filter local copies of POST, GET & COOKIE data
			// Unset global versions to prevent access to un-filtered
			$this->filterInputs($filter);
			
			$_POST = null;
			$_GET = null;
			$_COOKIE = null;
			
		}
	}
	
	public function filterInputs($filter = false) {
		
		if ($filter == false) return;
		if (!is_callable($filter)) 
			throw new \Exception('Specified filter is not callable.');
		
		$this->recurse($this->post, $filter);
		$this->recurse($this->get, $filter);
		$this->recurse($this->cookie, $filter);
	
	}
	
	protected function recurse(&$input, &$function) {
	
		if (is_array($input))
			foreach ($input as $name => &$value)
				$this->recurse($value, $function);
		else
			$function($input);
			
	}
	
	public function __get($name) {
	
		if (isset($this->$name)) return $this->$name;
		
		throw new \Exception('Invalid property specified.');
		
	}

}