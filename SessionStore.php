<?PHP

namespace 'Firelit';

abstract class SessionStore {
	
	// Set an array of values to be stored (treat $expireSeconds as garbage collection trigger more than session limiter)
	abstract public function store($array);
	
	// Fetch all values for a session
	abstract public function fetch();
	
	// Destroy a session and all stored values
	abstract public function destroy();
	
	// Instantiate the child class
	static function init($type = 'PHP') {
		// init accepts additional arguments and passes them to the instantiation of the class
		
		$args = func_get_args();	
		array_shift($args); // Remove the first argument before passing to object constructor
		
		$class = __class__ . $type;
		$reflect  = new ReflectionClass($class);
		return $reflect->newInstanceArgs($args);
		
	}
	
}