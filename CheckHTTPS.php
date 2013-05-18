<?PHP

namespace "Firelit";

class CheckHTTPS {
	// Check to see if the connection is secure, and if not perform an action
	
	static public function isSecure() {
		
		return ($_SERVER["HTTPS"] == "on");
		
	}
	
	static public function redirect($preRedirectFunction = false) {
		// Force page access by SSL only
		
		if (!self::isSecure()) {
			
			if (is_callable($preRedirectFunction)) {
				// Pass a closure to do work (eg, log the redirect)
				$preRedirectFunction();
			}
			
			if (headers_sent()) self::error();
			
			$newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
			
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: '. $newurl);
			
			exit;
			
		}
		
	}
	
	static public function error($errorCode = 400, $body = false, $preExitFunction = false) {
		
		if (!self::isSecure()) {
			
			if (is_callable($preExitFunction)) {
				// Pass a closure to do work (eg, log the redirect)
				$preExitFunction();
			}
			
			if (!$body) $body = 'HTTPS required.';
			
			EndWithError::now($errorCode, $body);
			
		}
		
	}
	
}