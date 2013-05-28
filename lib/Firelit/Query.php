<?PHP

namespace Firelit;

class Query {
	// Database connection & interaction class
	
	// Global connection & state variables 
	public static $config = false, $conn = false;
	
	public static $validTableName = '/^[a-zA-Z_][a-zA-Z0-9_]*$/'; // Regex for acceptable table names
	public static $validColName = '/^[a-zA-Z_][a-zA-Z0-9_]*$/'; // Regex for acceptable column names
	
	// Object variables
	private $res = false;
	private $sql;
	
	public function __construct() {
		// Connect to the database
		
		if (!self::$conn) $this->connect();
		
	}	
	
	public static function config($config) {
		
		/*
			Expects an associative array:
			
			$config = array(
				'type' => 'mysql',
				'db_name' => 'database',
				'db_host' => 'localhost', // Can be hostname or IP address
				'db_port' => '3306', // Can be left undefined to connect to default port
				'db_user' => 'username',
				'db_pass' => 'password'
			);
			
			-OR-
			
			$config = array(
				'type' => 'other',
				'dsn' => 'sqlite::memory'
			);
		*/
		
		if (!is_array($config)) 
			throw new \Exception('Database connection configuration not provided.');
		
		self::$config = $config;
		
	}
	
	public static function connect() {
		
		if (!self::$config) 
			throw new \Exception('Database connection configuration not provided.');
				
		try {
			
			if (self::$config['type'] == 'other') {
				
				self::$conn = new \PDO(self::$config['dsn']);
				
			} elseif (self::$config['type'] == 'mysql') {
				
				$dsn = 'mysql:dbname='. self::$config['db_name'] .';host='. self::$config['db_host'];
				if (strlen(self::$config['db_port'])) $dsn .= ';port='. self::$config['db_port'];
				
				self::$conn = new \PDO($dsn, self::$config['db_user'], self::$config['db_pass'], array( \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8mb4' ));
				
			} else {
			
				throw new \Exception('Invalid database type specified in config.');
				
			}
			
		} catch (PDOException $e) {
			
			throw new \Exception('Unable to connect to database: '. $e->getMessage());
			
		}
		
		return self::$conn;
		
	}
	
	public function query($sql, $data = array()) {
		// Execute the SQL query
		// Pass $sql statement and $data to bind with the $keys matching placeholders (see PDO prepare docs for details)
		// Example: query( "SELECT * FROM `table` WHERE `col`=:theval", array(':theval' => 'Hello!') )
		
		$binder = $this->binderPrep($data);
		
		$this->sql = self::$conn->prepare($sql);
		
		if (!$this->sql)
			throw new \Exception('Query statement could not be prepared: '. print_r(self::$conn->errorInfo(), true));
			
		return $this->res = $this->sql->execute($binder);
		
	}
	
	public function insert($table, $array) {
		// Preform an insert on the table
		// Enter an associative array for $array with column names as keys
		
		if (!preg_match(self::$validTableName, $table)) 
			throw new \Exception('Invalid database table name specified.');
		
		$this->sql = self::$conn->prepare("INSERT INTO `". $table ."` ". self::prepInsert($array));
		
		if (!$this->sql)
			throw new \Exception('Query statement could not be prepared: '. print_r(self::$conn->errorInfo(), true));
			
		$binder = $this->binderPrep($array);
		
		return $this->res = $this->sql->execute($binder);
		
	}
	
	public function replace($table, $array) {
		// Preform an replace on the table
		// Enter an associative array for $array with column names as keys
		
		if (!preg_match(self::$validTableName, $table)) 
			throw new \Exception('Invalid database table name specified.');
		
		$this->sql = self::$conn->prepare("REPLACE INTO `". $table ."` ". self::prepInsert($array));
		
		$binder = $this->binderPrep($array);
		
		return $this->res = $this->sql->execute($binder);
		
	}
	
	public function select($table, $select = array(), $where = 1, $whereData = array(), $limit = false, $range = false) {
		// Preform an select on the table
		// Enter column names in $select array or leave blank (or false) for all (ie, *)
		// Enter where clause into $where or leave as 1 to select all
		// Enter $whereData as associative array with data to be escaped and inserted into where clause (see PDO prepare docs for details)
		// $limit and $range should be integers used as SQL LIMIT
		
		if (!preg_match(self::$validTableName, $table)) 
			throw new \Exception('Invalid database table name specified.');
		
		if (is_array($select) && sizeof($select)) {
			
			$selectSql = '';
			
			foreach ($select as $col) {
				
				if (!preg_match(self::$validColName, $col)) 
					throw new \Exception('Invalid database column name specified.');
				
				$selectSql .= ', `'. $select .'`';
				
			}
			
			$selectSql = substr($selectSql, 2);
			
		} else $selectSql = '*';
		
		$sql = "SELECT ". $selectSql ." FROM `". $table ."` WHERE ". $where;
		
		if ($limit !== false) {
			$sql .= " LIMIT ". intval($limit);
			if ($range !== false) $sql .= ", ". intval($range);
		}
		
		$this->sql = self::$conn->prepare($sql);

		if (!$this->sql)
			throw new \Exception('Query statement could not be prepared: '. print_r(self::$conn->errorInfo(), true));
			
		$binder = $this->binderPrep($whereData);
		
		return $this->res = $this->sql->execute($binder);
		
	}
	
	public function update($table, $array, $where, $whereData = array(), $limit = false, $range = false) {
		// Preform an update on the table
		// Enter an associative array for $array with column names as keys
		// Enter $whereData as associative array with data to be escaped and inserted into where clause (see PDO prepare docs for details)
		// $where placeholders (referenced in $whereData) cannot be column names used in the $array
		
		if (!preg_match(self::$validTableName, $table)) 
			throw new \Exception('Invalid database table name specified.');
		
		if (sizeof(array_intersect_key($array, $whereData)))
			throw new \Exception('Conflicting parameter names for binding.');
		
		$this->sql = self::$conn->prepare("UPDATE `". $table ."` SET ". self::prepUpdate($array) ." WHERE ". $where);
		
		if (!$this->sql)
			throw new \Exception('Query statement could not be prepared: '. print_r(self::$conn->errorInfo(), true));
			
		$binder = $this->binderPrep($array);
		
		$binder = $this->binderPrep($whereData, $binder);
		
		return $this->res = $this->sql->execute($binder);
		
	}
	
	public function delete($table, $where, $whereData = array(), $limit = false, $range = false) {
		// Preform a delete on the table
		// Enter $whereData as associative array with data to be escaped and inserted into where clause (see PDO prepare docs for details)
		
		if (!preg_match(self::$validTableName, $table)) 
			throw new \Exception('Invalid database table name specified.');
		
		$sql = "DELETE FROM `". $table ."` WHERE ". $where;
		
		if ($limit !== false) {
			$sql .= " LIMIT ". intval($limit);
			if ($range !== false) $sql .= ", ". intval($range);
		}
		
		$this->sql = self::$conn->prepare($sql);
		
		if (!$this->sql)
			throw new \Exception('Query statement could not be prepared: '. print_r(self::$conn->errorInfo(), true));
			
		$binder = $this->binderPrep($whereData);
		
		return $this->res = $this->sql->execute($binder);
		
	}
	
	protected function binderPrep(&$array, $binderIn = false) {
		
		$binder = (is_array($binderIn) ? $binderIn : array());
		
		foreach ($array as $col => &$val) // By reference for db driver purposes
			$binder[(preg_match('/^:/', $col) ? '' : ':') . $col] = $val; 
		
		return $binder;
		
	}
	
	public function getRes() {
		return $this->res;
	}
	
	public function getRow() {
		if (!$this->res) return false;
		return $this->sql->fetch(\PDO::FETCH_ASSOC);
	}
	
	public function getAll() {
		if (!$this->res) return false;
		return $this->sql->fetchAll(\PDO::FETCH_ASSOC);
	}
	
	public function getNewId() {
		return self::$conn->lastInsertId();
	}
	
	public function getAffected() {
		return $this->sql->rowCount();
	}
	
	public function getNumRows() {
		// May not always return the correct number of rows
		// See note at http://php.net/manual/en/pdostatement.rowcount.php
		return $this->sql->rowCount();
	}
	
	public function getError() {
		$e = $this->sql->errorInfo();
		return $e[2]; // Driver specific error message.
	}
	
	public function getErrorCode() {
		$e = $this->sql->errorInfo();
		return $e[1]; // Driver specific error code.
	}
	
	public function success() {
		return $this->res;
	}
	
	public function logError(LogEntry $logger, $file, $line) {
		if (!$this->res) 
			$logger->now(3, 'MySql error ('. $this->getErrorCode() .', '. $this->getError() .', '. $this->sql .')', $file, $line);
			
		return $this->res;
	}
	
	public static function prepInsert(&$array) {
		// Prepare an INSERT SQL statement ready for a data bind
		
		if (!is_array($array)) 
			throw new Exception('Method expects an array.');
		
		$sql1 = '';
		$sql2 = '';
		
		foreach ($array as $col => $val) {
			
			if (!preg_match(self::$validColName, $col)) 
				throw new \Exception('Invalid database column name specified.');
			
			$sql1 .= ', `'. $col .'`';
			
			if (is_array($val) && ($val[0] == 'SQL')) {
				$sql2 .= ", ". $val[1];
				unset($array[$col]); // Remove it so that it's not added to the binder
			} else {
				$sql2 .= ", :". $col;
			}
			
		}
		
		return '( '. substr($sql1, 2) . ' ) VALUES ( '. substr($sql2, 2) .' )';
			
	}
	
	public static function prepUpdate(&$array) {
		// Prepare an UPDATE SQL statement ready for a data bind
		
		if (!is_array($array)) 
			throw new \Exception('Method expects an array.');
	
		$sql = '';
		
		foreach ($array as $col => $val) {
			
			if (!preg_match(self::$validColName, $col)) 
				throw new \Exception('Invalid database column name specified.');
			
			if (is_array($val) && ($val[0] == 'SQL')) {
				$sql .= ', `'. $col .'` = '. $val[1];
				unset($array[$col]); // Remove it so that it's not added to the binder
			} else {
				$sql .= ', `'. $col .'` = :'. $col;
			}
			
		}
		
		return substr($sql, 2);
		
	}
	
	
	public static function searchSer($intArray) {
		// Searchable serialize (int only)
		// Makes an array of ints easily searchable in a db table
		if (!is_array($intArray) || !sizeof($intArray)) return ';;';
		
		foreach ($intArray as $i => $v) $intArray[$i] = intval($v);
		
		return ';'.implode(';', $intArray).';';
		
	}
	
	public static function searchUnser($string) {
		// Searchable unserialize (int only)
		if (strlen($string) < 3) return array();
		$intArray = explode(';', substr($string, 1, -1));
		
		foreach ($intArray as $i => $v) $intArray[$i] = intval($v);
		
		return $intArray;
		
	}
	
	public function __clone() {
		$this->res = false;
		$this->sql = false;
	}
	
}
