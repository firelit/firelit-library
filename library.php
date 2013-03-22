<?PHP

function createKey($keyLen) { 
	// Create a random key of arbitrary length
	
  $symbArray = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $symbArrayLength = strlen($symbArray);
  
  $key = preg_replace('/[^0-9a-zA-Z]+/', '', base64_encode(hash('sha256', microtime() . $_SERVER['REMOTE_ADDR'] . $_SERVER['REMOTE_PORT'], true))); 
  $key = substr($key, mt_rand(0, 5), round($keyLen / 2));
  
  while (strlen($key) < $keyLen)
  	$key .= substr($symbArray, mt_rand() % $symbArrayLength, 1); 

	return $key;
}

function returnErr($httpErrNum, $msg) {
	// Return an HTTP error to the browser
	
	switch (intval($httpErrNum)) {
		case 400:
			header("HTTP/1.1 400 Bad Request");
			$httpMsg = 'Bad Request';
			// The request could not be understood by the server due to malformed syntax.
			break;
		case 403:
			header("HTTP/1.1 403 Forbidden");
			$httpMsg = 'Forbidden';
			// The server understood the request, but is refusing to fulfill it.
			break;
		case 404:
			header("HTTP/1.1 404 Not Found");
			$httpMsg = 'File Not Found';
			// The server has not found anything matching the Request-URI.
			break;
		case 500:
			header("HTTP/1.1 500 Internal Server Error");
			$httpMsg = 'Internal Server Error';
			// The server encountered an unexpected condition which prevented it from fulfilling the request. 
			break;			
		case 503:
			header("HTTP/1.1 503 Service Unavailable");
			$httpMsg = 'Service Unavailable';
			// The server is currently unable to handle the request due to a temporary overloading or maintenance of the server.
			break;
		default:
			header("HTTP/1.1 500 Internal Server Error");
			$httpMsg = 'Internal Server Error';
			// The server encountered an unexpected condition which prevented it from fulfilling the request.
			break;
	}												
	echo '<html><head><title>Error '. intval($httpErrNum) .' - '. $httpMsg .'</title></head>';
	echo '<body style="background-color:#D9D9D9;"><div style="width:500px;margin:40px auto;padding:20px 40px;border:10px solid #737382;background-color:white;color:#474750;font-family:arial;border-radius:10px;box-shadow: 0 3px 5px 5px #B9B9B9;">';
	echo '<h1 style="font-size:1em;">Error '. intval($httpErrNum) .' - '. $httpMsg .'</h1>';
	echo '<p style="font-size:1em;">'. $msg .'</p>';
	echo '<p style="font-size:1em;"><a href="/" style="color:#474750;font-size:.8em;">Home Page</a></p>';
	echo '</div></body></html>';
	exit;
}

function forceSSL($redirect = true) {
	// Force page access by SSL only
	if ($_SERVER["HTTPS"] != "on") {
		if (!$redirect) returnErr(400, 'This page only accept secure (https) connections.');
		$newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '. $newurl);
		exit;
	}
}

function validEmail($emailAddy) {
	// Check email address for valid format
	$preg = "/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,})$/";
	if (!preg_match($preg, $emailAddy)) return false;
	else return true;
}

function addressFix($address) {
	// Fix address case
	
	$address = lower(trim($address));
	if (strlen($address) == 0) return '';
	
	$address = str_replace(array('`','—','–','  ','--'), array("'",'-','-',' ','-'), $address);
	$address = preg_replace("/([a-z]+[\-'])([a-z]{2})/e", "'$1' . ucword('$2')", $address);
	$address = preg_replace("/([0-9#])([a-z])(\b|[0-9])/e", "'$1' . ucword('$2') . '$3'", $address);
	
	$patterns = array('/p\.o\.(\s?)/i',	'/^po\s/i',	'/^po\.(\s?)/i'); 
	$replacew = array('PO ', 						'PO ',			'PO '); 
	$address = preg_replace($patterns, $replacew, $address);
	
	$patterns = array('/\bn(\.?\s?)e(\.?)\s/i',	'/\bn(\.?\s?)e(\.?)$/i',	
										'/\bn(\.?\s?)w(\.?)\s/i',	'/\bn(\.?\s?)w(\.?)$/i',	
										'/\bs(\.?\s?)e(\.?)\s/i',	'/\bs(\.?\s?)e(\.?)$/i',	
										'/\bs(\.?\s?)w(\.?)\s/i',	'/\bs(\.?\s?)w(\.?)$/i',
										'/\br(\.?\s?)r(\.?)\s/i'); 
	$replacew = array('NE ', 'NE', 'NW ', 'NW', 'SE ', 'SE', 'SW ', 'SW', 'RR '); 
	$address = ucword(preg_replace($patterns, $replacew, $address));
	
	return $address;
}

function nameFix($name) {
	// Fix name case
	
	if (preg_match("/\b(Van|De|Di)[A-Z][a-z]+/", $name)) $compName = true; else $compName = false; // Will be all lower case, next
	$name = ucword(lower(trim($name)));
	
	if (strlen($name) == 0) return '';

	$name = str_replace(array('`','—','–','  ','--','And '), array("'",'-','-',' ','-','and '), $name); 
	$name = preg_replace("/([A-Za-z]+[\-'])([A-Za-z]{2})/e", "'$1' . ucword('$2')", $name);
	$name = preg_replace("/([a-z])[\+&]([a-z])/e", "'$1' . ' & ' . ucword('$2')", $name);
	$name = preg_replace("/(Mc)([a-z]+)/e", "'$1' . ucword('$2')", $name);
	$name = preg_replace("/(\b)(Ii|Iii|Iv)(\b)/e", "'$1' . upper('$2') . '$3'", $name);
	if ($compName)
		$name = preg_replace("/\b(Van|De|Di)([a-z]+)/e", "'$1' . ucword('$2')", $name); 
		
	return $name;
}

function cleanUTF8(&$input, $stripSlashes = true, $lineBreaksOk = true) {
	// Clean input for UTF-8 valid characters
	
	if ($stripSlashes) $stripSlashes = get_magic_quotes_gpc();
	
	if (is_array($input)) 
		foreach ($input as $k => $v) cleanUTF8($input[$k], $stripSlashes, $lineBreaksOk);
	else {
		
		if ($stripSlashes) $input = stripslashes($input);
		$input = mb_convert_encoding($input, "UTF-8", "UTF-8");
		if ($lineBreaksOk) $input = str_replace("\n", ($temp = '[{'.mt_rand(1000000, 9999999).'}]'), $input);
		$input = preg_replace('!\p{C}!u', '', $input);
		if ($lineBreaksOk) $input = str_replace($temp, "\n", $input);
		
	}
}

function html($var) {
	// HTML-escaping with UTF-8 support
	$out = htmlentities($var, ENT_COMPAT, 'UTF-8');
	if ($out == '') $out = htmlentities(mb_convert_encoding($var, "UTF-8"), ENT_COMPAT, 'UTF-8'); // TEMP WORK AROUND, INVALID UTF-8 IN DATABASE?
	return $out;
}

function xml($str) { 
	// XML-escaping
	$str = htmlentities($str);
  $xml = array('&#34;','&#38;','&#38;','&#60;','&#62;','&#160;','&#161;','&#162;','&#163;','&#164;','&#165;','&#166;','&#167;','&#168;','&#169;','&#170;','&#171;','&#172;','&#173;','&#174;','&#175;','&#176;','&#177;','&#178;','&#179;','&#180;','&#181;','&#182;','&#183;','&#184;','&#185;','&#186;','&#187;','&#188;','&#189;','&#190;','&#191;','&#192;','&#193;','&#194;','&#195;','&#196;','&#197;','&#198;','&#199;','&#200;','&#201;','&#202;','&#203;','&#204;','&#205;','&#206;','&#207;','&#208;','&#209;','&#210;','&#211;','&#212;','&#213;','&#214;','&#215;','&#216;','&#217;','&#218;','&#219;','&#220;','&#221;','&#222;','&#223;','&#224;','&#225;','&#226;','&#227;','&#228;','&#229;','&#230;','&#231;','&#232;','&#233;','&#234;','&#235;','&#236;','&#237;','&#238;','&#239;','&#240;','&#241;','&#242;','&#243;','&#244;','&#245;','&#246;','&#247;','&#248;','&#249;','&#250;','&#251;','&#252;','&#253;','&#254;','&#255;');
  $html = array('&quot;','&amp;','&amp;','&lt;','&gt;','&nbsp;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&sup1;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
  $str = str_replace($html,$xml,$str); 
  $str = str_ireplace($html,$xml,$str); 
  return $str; 
}

function lower($txt) {
	return mb_strtolower($txt, 'UTF-8');
}

function upper($txt) {
	return mb_strtoupper($txt, 'UTF-8');
}

function ucword($txt) {
	return preg_replace('/\b(\s?)(.)(\S*)\b/ue', 'stripslashes("$1"). upper(stripslashes("$2")) . stripslashes("$3")', $txt);
	// return mb_convert_case($txt, MB_CASE_TITLE, 'UTF-8'); // This is also doing a lower() first, not like ucwords()
}

function getSubDomain() {
	return preg_replace('/^(.+?).'. DOM_NAME .'$/', '$1', $_SERVER['HTTP_HOST']);
}

function getAccount($slug, $byId = false) {
	// TODO - move to class
	global $_CACHE;
	
	if (!is_array($_CACHE['account'])) $_CACHE['account'] = array();
	if (isset($_CACHE['account'][$slug])) return $_CACHE['account'][$slug];
	
	if ($byId) $sql = "SELECT * FROM `Accounts` WHERE `id`='". asl($slug) ."' LIMIT 1";
	else $sql = "SELECT * FROM `Accounts` WHERE `slug`='". asl($slug) ."' LIMIT 1";

  $q = new Firelit\Query($sql);
	if (!$q->success(__FILE__, __LINE__)) return false;
	
	$account = $q->getRow();
	
	$_CACHE['account'][$account['slug']] = $account;
	$_CACHE['account'][$account['ID']] = &$_CACHE['account'][$account['slug']];
	
	return $account;
}

function array2xml(&$simpleXMLElement, $arrayIn) {
	// Function definition to convert array to xml
	foreach($arrayIn as $key => $value) {
	  if (is_array($value)) {
      if (!is_numeric($key)) {
				$subnode = $simpleXMLElement->addChild($key);
				array2xml($subnode, $value);
      } else {
				array2xml($simpleXMLElement, $value);
      }
	  } else {
	  	$simpleXMLElement->addChild($key, $value);
	  }
	}
}

function csvData($input) {
	// CSV-escaping for a CSV cell
	$output = str_replace('"', '""', $input); 
	return '"'.utf8_decode($output).'"';
}

function searchSer($intArray) {
	// Searchable serialize (int only)
	// Makes an array of ints easily searchable in a db table
	if (!is_array($intArray) || !sizeof($intArray)) return ';;';
	foreach ($intArray as $i => $v) $intArray[$i] = intval($v);
	return ';'.implode(';', $intArray).';';
}

function searchUnser($string) {
	// Searchable unserialize (int only)
	if (strlen($string) < 3) return array();
	$intArray = explode(';', substr($string, 1, -1));
	foreach ($intArray as $i => $v) $intArray[$i] = intval($v);
	return $intArray;
}