<?php

namespace 'Firelit';

abstract class EmailSender {
	
	abstract public function send(Email $email);
	
	static public function init($type = 'SMTP') {

		$args = func_get_args();	
		array_shift($args); // Remove the first argument before passing to object constructor
		
		$class = __class__ . $type;
		$reflect  = new ReflectionClass($class);
		return $reflect->newInstanceArgs($args);
		
	}
	
}