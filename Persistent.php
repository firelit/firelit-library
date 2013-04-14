<?PHP

abstract class Persistent {

	// Bare minimum for Persistent class
	abstract public function set($key, $name, $value);
	
	abstract public function get($key, $name);
	
}