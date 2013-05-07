<?PHP

namespace 'Firelit';

class EmailStoreDB extends EmailStore {
	
	private static $config = array(
		'tableName' => 'EmailQueue',
		'expireSeconds' => 604800 // Emails expire in 7 days by default
	);
	
	private $query, $table;
	
	public function __construct(Query $queryObject, $dbTable = 'EmailQueue') {
		$this->query = $queryObject;
		$this->table = $dbTable;
	}
	
	public static function config($config) {
		self::$config = array_merge(self::$config, $config);
	}
	
	public function store(Email $email, $expiresSeconds = false) {
		
		if (!$expiresSeconds) $expiresSeconds = self::$config['expireSeconds'];
		
		$this->query->insert(self::$config['tableName'], array(
			'to' => $email->to,
			'subject' => $email->subject,
			'email' => serialize($email),
			'status' => 'QUEUED',
			'expires' => array('SQL', 'DATE_ADD(NOW(), INTEREVAL '. $expires .' SECOND)')
		));
		
	}
	
	public function storeAndSend(Email $email, EmailSender $sender, $expiresSeconds = false) {
		
		if (!$expiresSeconds) $expiresSeconds = self::$config['expireSeconds'];
		
		$id = $this->store($email, $expireSeconds);
	
		$this->sendOne($id, $sender);
		
	}
	
	public function markSending($id) {
		
		$this->query->update(self::$config['tableName'], array(
			'status' => 'SENDING'
		), "`id`=:id AND `status`='QUEUED' AND `expires`>NOW()", array('id' => $id), 1);
		
		return (bool) $this->query->getAffected();
		
	}
	
	public function markSent($id) {
		
		$this->query->update(self::$config['tableName'], array(
			'status' => 'SENT'
		), "`id`=:id", array('id' => $id), 1);
		
		return (bool) $this->query->getAffected();
		
	}
	
	public function markQueued($id) {
		
		$this->query->update(self::$config['tableName'], array(
			'status' => 'QUEUED'
		), "`id`=:id AND `status`!='SENT' AND `expires`>NOW()", array('id' => $id), 1);
		
		return (bool) $this->query->getAffected();
		
	}
	
	public function sendAll(EmailSender $sender) {
		// TODO - query all and the sendOne()
	}
	
	public function sendOne($id, EmailSender $sender) {
		// TODO - get from db, if sent ignore, mark sending, send, mark sent
		
		if ($this->markSending($id)) {
		
			// TODO - Send it here!
			
			if ($this->markSent($id))
				throw new Exception('Error marking sent email as sent: '. $id);
			
		}
	}
	
	public function purgeSent($olderThanSeconds) {
		// TODO - delete query
	}
	
	public function purgeAll($olderThanSeconds) {
		// TODO - delete query
	}
	
	public function purgeExpired($olderThanSeconds) {
		// TODO - delete query
	}
	
	public static function install(Query $query) {
		// One-time install
		// Create the supporting tables in the db
		
		// TODO
		// TODO - TEST! utf8mb4 may not work....
		$sql = "CREATE TABLE IF NOT EXISTS `". self::$config['tableName'] ."` (
			  `id` int,
			  `to` text,
			  `subject` text,
			  `email` longtext,
			  `status` enum('QUEUED', 'SENDING', 'SENT', 'ERROR'),
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `expires` datetime NOT NULL,
			  `sent` datetime NOT NULL,
			  PRIMARY KEY `id` (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;";
			
		$q = $query->query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed! ('. __FILE__ .':'. __LINE__ .')');
			
		return $query->insertId();
		
			
	}
}
