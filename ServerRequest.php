<?PHP

namespace Firelit;

class ServerRequest {
	
	// All properties accessible via magic getter method
	private $host, $path, $method, $secure, $referer, $cli;
	private $post, $get, $cookie;

	public function __construct() { 
		
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->path = $_SERVER['REQUEST_URI'];
		$this->secure = ($_SERVER['HTTPS'] == 'on');
		$this->host = $_SERVER['HTTP_HOST'];
		$this->referer = $_SERVER['HTTP_REFERER'];
		$this->cli = (php_sapi_name() == 'cli');
		
		$this->post = $_POST;
		$this->get = $_GET;
		$this->cookie = $_COOKIE;
		
	}
	
	public function filterInputs() {
		
		Strings::cleanUTF8($this->post);
		Strings::cleanUTF8($this->get);
		Strings::cleanUTF8($this->cookie);
	
	}
	
	public function __get($name) {
	
		if (isset($this->$name)) return $this->$name;
		
		throw new \Exception('Invalid property specified.');
		
	}

}