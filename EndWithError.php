<?PHP

namespace 'Firelit';

class EndWithError {
	
public static $errorTemplate = <<<EOT
	<html><head><title>{{title}}</title></head>
	<body style="background-color:#D9D9D9;"><div style="width:500px;margin:40px auto;padding:20px 40px;border:10px solid #737382;background-color:white;color:#474750;font-family:arial;border-radius:10px;box-shadow: 0 3px 5px 5px #B9B9B9;">
		<h1 style="font-size:1em;">{{header}}</h1>
		<p style="font-size:1em;">{{message}}</p>
		<p style="font-size:1em;"><a href="/" style="color:#474750;font-size:.8em;">Home Page</a></p>
	</div></body></html>
EOT;

	public static function now($errorCode, $body = false) {
		/* 
			Overview of most popular error codes:
				400 = Bad Request
				401 = Unauthorized
				403 = Forbidden
				404 = Not Found
				500 = Internal Server Error
				503 = Service Unavailable
		*/
		
		http_response_code($errorCode);
		
		if ($body) echo $body;
		
		exit;
		
	}
	
	public static function createErrorBody($title, $header, $message) {
		
		$body = self::$errorMessageTemplate;
		
		$body = str_replace('{{title}}', $title, $body);
		$body = str_replace('{{header}}', $header, $body);
		$body = str_replace('{{message}}', $message, $body);
		
		return $body;
		
	}
	
}