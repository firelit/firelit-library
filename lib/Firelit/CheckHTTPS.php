<?PHP

namespace Firelit;

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
			
			$newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
			
			if (headers_sent()) {
				self::error(400, function() {
					echo ' This page must be accessed securely: <a href="'. html_entities($newurl) .'">Click Here</a>';
				});
			}
			
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: '. $newurl);
			
			exit;
			
		}
		
	}
	
	static public function error($errorCode = 400, $preExitFunction = false) {
		
		if (!self::isSecure()) {
			
			http_response_code($errorCode);
			
			if (is_callable($preExitFunction)) {
				// Pass a closure to do work (eg, log the redirect)
				$preExitFunction();
			}
			
			exit;
			
		}
		
	}
	
}