<?PHP

namespace 'Firelit';

class Config {
	// DB connection info only?
	// Load all other data from DB?
	
	public static $DB = array(
		'DB_NAME' => '',
		'DB_IP' => '',
		'DB_USER' => '',
		'DB_PASS' => ''
	);
	
	public static $Session = array(
		'KEY_NAME' => 'firelit-sid', // Name of the cookie stored in remote browser
		'SID_LEN' => 32, // Length of key in charchters
		'DAYS_EXPIRE' => 7, // How long session variables are stored locally (n/a if USE_DB is false)
		'USE_DB' => false // Instead of native PHP session support, for multi-server environment
	);
	
}

// Can be upgraded/replaced/loaded from elsewhere in the future? Use 'implements'?
// Short and easily callable?
// Easily extendable? 
// One file for all vendors?
// Easily updatable when new s/w added?
// Default values definable?