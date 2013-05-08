<?PHP

namespace 'Firelit';

class EmailStoreDB extends EmailStore {
	
	private static $config = array(
		'tableName' => 'EmailQueue',
		'expireSeconds' => 604800 // Emails expire in 7 days by default
	);
	
	private $query, $table, $cache;
	
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
		
		if (!$this->query->getAffected()) 
			throw new Exception('Error while storing email.');
			
		$id = $this->query->getNewId();
		
		$this->cache[$id] = $email;
		
		return $id;
		
	}
	
	public function fetch($id, $filterExpired = true) {
		
		if (isset($this->cache[$id])) return $this->cache[$id];
		
		$this->query->select(self::$config['tableName'], array('email'), '`id`=:id'. ($filterExpired ? ' AND `expires`>NOW()'), array('id' => $id));
		
		if ($row = $this->query->fetch()) return unserialize($row['email']);
		else return false;
			
	}
	
	public function storeAndSend(Email $email, EmailSender $sender, $expiresSeconds = false) {
		
		if (!$expiresSeconds) $expiresSeconds = self::$config['expireSeconds'];
		
		$id = $this->store($email, $expireSeconds);
	
		return $this->sendOne($id, $sender);
		
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
		
		$this->query->select(self::$config['tableName'], '*', "`status`='QUEUED' AND `expires`>NOW() ORDER BY `created` ASC");
		
		while ($row = $this->query->getRow()) {
			
			// Save a fetch
			$this->cache[$row['id']] = unserialize($row['email']);
			
			$this->sendOne($row['id'], $sender);
			
		}
		
	}
	
	public function sendOne($id, EmailSender $sender) {
		
		if (!$this->markSending($id)) return false;
	
		$email = $this->fetch($id);
		
		if (!$email) return false;
		
		try {
			
			$sender->send($email);
			
		} catch (\Exception $e) {
			
			// Throw exception instead?
			
			if (!$this->markQueued($id))
				throw new Exception('Error re-queueing email: '. $id);
				
			return false;
			
		}
		
		if (!$this->markSent($id))
			throw new Exception('Error marking sent email as sent: '. $id);
		
		return true;
		
	}
	
	public function purgeSent($olderThanSeconds) {
		
		$this->query->delete(self::$config['tableName'], "`status`='SENT' AND `sent`<DATE_SUB(NOW(), INTERVAL ". $olderThanSeconds ." SECOND)");
		
	}
	
	public function purgeAll($olderThanSeconds) {
		
		$this->query->delete(self::$config['tableName'], "`sent`<DATE_SUB(NOW(), INTERVAL ". $olderThanSeconds ." SECOND)");
		
	}
	
	public function purgeExpired($olderThanSeconds = 0) {
		
		$this->query->delete(self::$config['tableName'], "`expired`<=DATE_SUB(NOW(), INTERVAL ". $olderThanSeconds ." SECOND)");
		
	}
	
	public static function install(Query $query) {
		// One-time install
		// Create the supporting tables in the db
		
		// Running MySql >= 5.5.3 ? Use utf8mb4 insetad of utf8.
		$sql = "CREATE TABLE IF NOT EXISTS `". self::$config['tableName'] ."` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `to` text NOT NULL,
			  `subject` text NOT NULL,
			  `email` longtext NOT NULL,
			  `status` enum('QUEUED','SENDING','SENT','ERROR') NOT NULL,
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `expires` datetime NOT NULL,
			  `sent` datetime NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY (`status`),
			  KEY (`expires`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;"
			
		$q = $query->query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed! ('. __FILE__ .':'. __LINE__ .')');
			
		return $query->insertId();
		
	}
}
