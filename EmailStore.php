<?php

namespace Firelit;

abstract class EmailStore extends InitExtendable {
	
	abstract public function store(Email $email, $expiresSeconds = false);
	
	abstract public function fetch($id, $filterExpired = true);
	
	abstract public function storeAndSend(Email $email, EmailSender $sender, $expiresSeconds = false);
	
	abstract public function sendOne($id, EmailSender $sender);
	
	abstract public function sendAll(EmailSender $sender);
	
	abstract public function purgeSent($olderThanSeconds);
	
	abstract public function purgeAll($olderThanSeconds);
	
}