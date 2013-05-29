<?PHP

namespace Firelit;

class CheckHTTPS {
	// Check to see if the connection is secure, and if not perform an action
	
	static public function isSecure() {
		
		if (!isset($_SERVER['HTTPS'])) return false;
		
		return ($_SERVER["HTTPS"] == "on");
		
	}
	
	static public function redirect($preRedirectFunction = false) {
		// Force page access by SSL only
		
		if (!self::isSecure()) {
			
			if (is_callable($preRedirectFunction)) {
				// Pass a closure to do work (eg, log the redirect)
				$preRedirectFunction();
			}
			
			$newurl = self::getSecureURL();
			
			if (headers_sent()) {
				echo ' This page must be accessed securely: <a href="'. html_entities($newurl) .'">Click Here</a>';
				exit;
			}
			
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: '. $newurl);
			
			exit;
			
		}
		
	}
	
	static public function error($errorCode = 400, $preExitFunction = false) {
		
		if (!self::isSecure()) {
			
			if (!headers_sent())
				http_response_code($errorCode);
			
			if (is_callable($preExitFunction)) {
				// Pass a closure to do work (eg, log the redirect)
				$preExitFunction();
			}
			
			exit;
			
		}
		
	}
	
	static public function getSecureURL() {
		
		return "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	
	}
	
}