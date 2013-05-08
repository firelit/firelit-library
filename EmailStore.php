<?php

namespace 'Firelit';

abstract class EmailStore {
	
	abstract public function store(Email $email, $expiresSeconds = false);
	
	abstract public function fetch($id, $filterExpired = true);
	
	abstract public function storeAndSend(Email $email, EmailSender $sender, $expiresSeconds = false);
	
	abstract public function sendOne($id, EmailSender $sender);
	
	abstract public function sendAll(EmailSender $sender);
	
	abstract public function purgeSent($olderThanSeconds);
	
	abstract public function purgeAll($olderThanSeconds);
	
	static public function init($type = 'PHP') {

		$args = func_get_args();	
		array_shift($args); // Remove the first argument before passing to object constructor
		
		$class = __class__ . $type;
		$reflect  = new ReflectionClass($class);
		return $reflect->newInstanceArgs($args);
		
	}
	
}