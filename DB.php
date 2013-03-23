<?PHP

namespace 'Firelit';

include_once('library.php');

class DB {
	// Database connection & interaction class
	
	// Global connection & state variables 
	public static $conn = false;
	public static $database = false;
	
	// Object variables
	private $res = false, $sql;
	
	public function __construct() {
		// Connect to the database
		if (!self::$conn) $this->connect();
	}	
	
	public static function connect() {
		
		$dsn = 'mysql:dbname='. FIRELIT_DB_NAME .';host='. FIRELIT_DB_IP;
		
		try {
			$conn = new PDO($dsn, FIRELIT_DB_USER, FIRELIT_DB_PASS);
		} catch (PDOException $e) {
			throw new Exception('Unable to connect to database.');
		}

  	self::$conn = $conn;
  	return $conn;
  	
	}
	
	public function query($sql) {
		// Execute the SQL query
		return $this->res = self::$conn->query($sql);
	}
	
	public function insert($table, $array) {
		// Preform an insert on the table
		$sql = "INSERT INTO `". $table ."` ". self::toSQL('INSERT', $array);
		
		// Returns the number of affected rows
		return self::$conn->exec($sql);
	}
	
	public function replace($table, $array) {
		// Preform an replace on the table
		$sql = "REPLACE INTO `". $table ."` ". self::toSQL('INSERT', $array);
		
		// Returns the number of affected rows
		return self::$conn->exec($sql);
	}
	
	public function select($table, $where = 1, $limit = false, $range = false) {
		// Preform an select on the table
		$sql = "SELECT * FROM `". $table ."` WHERE ". $where;
		if ($limit !== false) {
			$sql .= " LIMIT ". intval($limit);
			if ($range !== false) $sql .= ", ". intval($range);
		}
		
		// Returns the result set for the query
		return $this->res = self::$conn->query($sql);
	}
	
	public function update($table, $array, $where, $limit = false, $range = false) {
		// Preform an update on the table
		$sql = "UPDATE `". $table ."` SET ". self::toSQL('UPDATE', $array) ." WHERE ". $where;
		if ($limit !== false) {
			$sql .= " LIMIT ". intval($limit);
			if ($range !== false) $sql .= ", ". intval($range);
		}
		
		// Returns the number of affected rows
		return self::$conn->exec($sql, $array);
	}
	
	public function delete($table, $where, $limit = false, $range = false) {
		// Preform a delete on the table
		$sql = "DELETE FROM `". $table ."` WHERE ". $where;
		if ($limit !== false) {
			$sql .= " LIMIT ". intval($limit);
			if ($range !== false) $sql .= ", ". intval($range);
		}
		
		// Returns the number of affected rows
		return self::$conn->exec($sql);
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
	
	public function success($file = false, $line = false) {
		if (!$this->res && $file) new LogIt(3, 'MySql error ('. $this->getError() .', '. $this->sql .')', $file, $line);
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
