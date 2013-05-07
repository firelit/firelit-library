<?php

abstract class EmailStore {
	
	abstract public function store($header, );
	
	abstract public function getAll();
	
	abstract public function getOne($id);
	
	abstract public function purgeSent($olderThan);
	
	abstract public function purgeAll($olderThan);
	
	static public function init($type = 'PHP') {
		$type = __class__ . $type;
		return new $type();
	}
	
}