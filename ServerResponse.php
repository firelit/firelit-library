<?PHP

namespace "Firelit";

class ServerResponse {
	
	private $outputBuffering = true;
	private $this->charset;
	
	public function __construct($ob = true, $charset = "UTF-8") { 
		// $ob: Turn output buffering on?
		// $charset: Specify the charset?
		$this->outputBuffering = $ob;
		$this->charset = $charset;
		
		// UTF-8 output by default
		mb_http_output($this->charset);
		
		if ($ob) {
			// Ouput buffer by default to prevent unforseen errors from printing to the page,
			// to make possible a special 500 error page if something comes up during processing,
			// to prevent flushing in strange places and partial page loads if a internal processes take too long,
			// and ability to redirect at any time if there is an issue
			
			// Run output through muli-byte filter to match the above-specified output encoding
			
			ob_start("mb_output_handler");
			
		}
		
	}
	
	public function contentType($type = false) {
		
		if (!$type) $type = "text/html";
		
		header("Content-Type: ". $type ."; charset=". strtolower($this->charset));
		
	}
	
	public function code($code) {
		
		http_response_code($code);
		
	}
	
	public function redirect($path, $type = 302, $end = true) {
		// $type should be one of the following:
		// 301 = Moved permanently
		// 302 = Temporary redirect
		// 303 = Perform GET at new location (instead of POST)
		
		$this->code($code);
		header('Location: '. $path);
		
		if ($this->outputBuffering)
			ob_end_clean();
		
		if ($end) exit;
		
	}
	
	public function flushBuffer() {
	
		if ($this->outputBuffering)
			ob_flush();
			
	}
	
	public function cleanBuffer() {
	
		if ($this->outputBuffering)
			ob_clean();
			
	}
	
	public function endBuffer() {
		// Call cleanBuffer first if you don't want anything getting out
		if ($this->outputBuffering)
			ob_end_flush();
			
	}
}