<?PHP

namespace 'Fireit';

class Query {
	/* Global connection & state variables */
	public static $conn = false;
	public static $database = false;
	
	/* Object variables */
	private $res, $sql;
	
	public function __construct($sql, $database = false) {
		// $sql can be a full SQL statement
		// OR false if it is to be defined later
		
		if (!function_exists('logIt')) throw new Exception('logIt() function not defined.');
		if (!function_exists('returnErr')) throw new Exception('returnErr() function not defined.');
		
		if (!self::$conn) $this->connect();
		if (!$database) $database = DB_NAME;
		if (self::$database != $database) $this->dbSelect($database);
		
		$this->sql = $sql;
		
		if (!$sql)
			$this->res = mysql_query($sql, self::$conn);
		else
			$this->res = false;
	}	
	
	public static function connect() {
		$conn = @mysql_connect(DB_IP, DB_USER, DB_PASS);
  	if (!$conn) returnErr(500, 'Internal Server Error #'.__LINE__);
  	self::$conn = $conn;
  	return $conn;
	}
	
	public static function dbSelect($db) {
		$res = @mysql_select_db($db, self::$conn);
 		if (!$res) returnErr(500, 'Internal Server Error #'.__LINE__);
 		self::$database = $db;
 		return $res;
	}
	
	public function getRes() {
		return $this->res;
	}
	
	public function getRow() {
		if (!$this->res) return false;
		return mysql_fetch_assoc($this->res);
	}
	
	public function getNewId() {
		return mysql_insert_id(self::$conn);
	}
	
	public function getAffected() {
		return mysql_affected_rows(self::$conn);
	}
	
	public function getNumRows() {
		return mysql_num_rows($this->res);
	}
	
	public function getError() {
		return mysql_error(self::$conn);
	}
	
	public function success($file, $line) {
		if (!$this->res) logIt('! MySql error ('. $this->getError() .', '. $this->sql .')', $file, $line);
		return $this->res;
	}
	
	public static function asl($txt) {
		if (!self::$conn) self::connect();
		if (is_array($txt)) throw new Exception('Must pass a string to add slashes.');
		return mysql_real_escape_string($txt);	
	}
	
	public static function toSQL($verb, $assocArray) {
		// $assocArray should be an array of 'raw' items (not yet sanitized for database)
		// If the type is ambiguous, the item should be array(type, value)
		// Acceptable types: boolean, integer, float, double, string, sql, serialize
		
		if ($verb == 'INSERT') {
			
			$sql1 = '';
			$sql2 = '';
			
			foreach ($assocArray as $key => $value) {
				
				switch (gettype($value)) {
					case 'boolean':
					case 'integer':
					case 'double':
					case 'string':
						$type = gettype($value);
						break;
					case 'array': $type = lower($value[0]); $value = $value[1]; break;
					case 'NULL': $type = 'string'; $value = ''; break;
					default: throw new Exception('Invalid type for '. __FUNCTION__ .'(): '. gettype($value));
				}
				
				$sql1 .= ', `'. self::asl($key) .'`';
				
				switch ($type) {
					case 'boolean':
						$sql2 .= ", ". ($value ? 'TRUE' : 'FALSE');
						break;
					case 'integer':
					case 'double':
					case 'float':
						$sql2 .= ", ". $value;
						break;
					case 'string':
						$sql2 .= ", '". self::asl($value) ."'";
						break;
					case 'sql':
						$sql2 .= ", ". $value;
						break;
					case 'serialize':
						$sql2 .= ", '". self::asl(serialize($value)) ."'";
						break;
					default: throw new Exception('Invalid type for '. __FUNCTION__ .'(): '. gettype($value));
				}
				
			}
			
			return '('. substr($sql1, 2) . ') VALUES ('. substr($sql2, 2) .')';
			
		} elseif ($verb == 'UPDATE') {
			
			$sql = '';
			
			foreach ($assocArray as $key => $value) {
				
				switch (gettype($value)) {
					case 'boolean':
					case 'integer':
					case 'double':
					case 'string':
						$type = gettype($value);
						break;
					case 'array': $type = lower($value[0]); $value = $value[1]; break;
					case 'NULL': $type = 'string'; $value = ''; break;
					default: throw new Exception('Invalid type for '. __FUNCTION__ .'(): '. gettype($value));
				}
				
				$sql .= ', `'. $key .'`=';
				
				switch ($type) {
					case 'boolean':
						$sql .= ($value ? 'TRUE' : 'FALSE');
						break;
					case 'integer':
					case 'double':
					case 'float':
						$sql .= $value;
						break;
					case 'string':
						$sql .= "'". self::asl($value) ."'";
						break;
					case 'sql':
						$sql .= $value;
						break;
					case 'serialize':
						$sql .= "'". self::asl(serialize($value)) ."'";
						break;
					default: throw new Exception('Invalid type for '. __FUNCTION__ .'(): '. gettype($value));
				}
				
			}
			
			return substr($sql, 2);
			
		} else throw new Exception("Invalid verb for toSQL();");
	}
	
}
