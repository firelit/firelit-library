<?PHP

class EmailStoreDB extends EmailStore {
	
	private $query, $table;
	
	public function __construct(Query $queryObject, $dbTable = 'EmailQueue') {
		$this->query = $queryObject;
		$this->table = $dbTable;
	}
	
	public function store($headers, $html, $text, $attachment = false, $expires = '2 WEEK') {
		$q = clone $this->query;
		
		$q->insert('EmailQueue', array(
			'headers' => $headers,
			'html' => $html,
			'text' => $text,
			'attachments' => $attachments,
			'expires' => array('SQL', 'DATE_ADD(NOW(), INTEREVAL '. $expires .')')
		));
		
	}
	
	public function getAll() {
		
	}
	
	public function getOne($id) {
		
	}
	
	public function purgeSent($olderThan) {
		
	}
	
	public function purgeAll($olderThan) {
		
	}
	
	static function install(Query $query) {
		// One-time install
		// Create the supporting tables in the db
		
		// TODO
		// TODO - TEST! utf8mb4 may not work....
		$sql = "CREATE TABLE IF NOT EXISTS `EmailQueue` (
			  `id` varchar(". $this->config['sidLen'] .") NOT NULL COLLATE utf8mb4_unicode_cs,
			  `headers` varchar(32) NOT NULL,
			  `html` longtext NOT NULL,
			  `text` longtext NOT NULL,
			  `attachments` longtext NOT NULL,
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `expires` datetime NOT NULL,
			  PRIMARY KEY  (`sid`,`name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;";
			
		$q = $query->query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed! ('. __FILE__ .':'. __LINE__ .')');
			
		return $query->insertId();
		
			
	}
}
