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


