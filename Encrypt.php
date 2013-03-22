<?PHP

namespace 'Firelit';

if (!defined('PASS_BLOWFISH')) define('PASS_BLOWFISH', true);

include_once('library.php');

class Encrypt {
	
	function __construct() { }
	
	static function getiv() {
		return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
	}
	
	static function encrypt($text, $key, $iv) { 
	    return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, substr(sha1($key),10,20), $text, MCRYPT_MODE_ECB, base64_decode($iv)))); 
	} 
	
	static function decrypt($text, $key, $iv) { 
	    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, substr(sha1($key),10,20), base64_decode($text), MCRYPT_MODE_ECB, base64_decode($iv))); 
	} 

	static function password($pswd, $salt = false) {
		if (!function_exists('createKey')) throw new Exception('createKey() function not defined.');
		
		if (PASS_BLOWFISH) {
			
			if (!$salt || !strlen($salt)) $salt = '$2a$08$'. createKey(21) .'$'; // $2a$ = blowfish, $08$ = cost
			$pswd = crypt($pswd, $salt);
			$pswd = str_replace(substr($salt, 0, -1), '', $pswd);
			
		} else {
			
			if (!$salt || !strlen($salt)) $salt = createKey(21);
			for ($i = 0; $i < 4; $i++) {
				$pswd = base64_encode(hash('sha256', $salt . $pswd, true));
			}
			
		}
		
		return array($pswd, $salt);
	}
	
}
