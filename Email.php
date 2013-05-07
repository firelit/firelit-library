<?PHP

namespace 'Firelit';

include_once('library.php');

class Email {
	
	private $htmlBody = '';
	private $txtBody = '';
	
	private $htmlFooter = '';
	private $txtFooter = '';
	
	private $sendHTML = false;
	private $headers;
	private $bcc = false;
	
	private $mime;
	
	public static $config = array(
		'smtp_server' => 'localhost',
		'smtp_port' => '25',
		'smtp_user' => '',
		'smtp_pass' => '',
		'smtp_from' => '',
		'bcc' => ''
	);
	
	public $emailUTF8 = true;
	public $attachments = array();
	
	public function __construct($to, $cc = false, $bcc = false) {
		
		require_once('Mail.php'); // For new PEAR mailer
		require_once('Mail/mime.php'); // For new HTML emails 
		
		$this->headers = array(
			'To' => $to,
			'Cc' => ($cc ? $cc : '')
		);
		
		$this->bcc = $bcc;
		
	}
	
	static public function config($config) {
		
		/*
			Expects an associative array:
			$config = array(
				'smtp_server' => 'localhost',
				'smtp_port' => '25',
				'smtp_user' => 'username',
				'smtp_pass' => 'password',
				'smtp_from' => 'itsfromme@example.com',
				'bcc' => 'bcc_on_everything@example.com'
			);
		*/
		
		self::$config = array_merge(self::$config, $config);	
		
	}
	
	public function setHeader($subject, $fromName, $replyTo = '') {
		$this->headers = array_merge($this->headers, array(
			'From' => '"'. str_replace('"', '', $fromName) .'" <'. self::$config['smtp_from'] .'>',
			'Reply-To' => $replyTo,
			'Date' => date('r'),
			'Subject' => $subject,
			'Message-ID' => $this->createMessageId()
		);
	}
	
	private function createMessageId() {
		$dateStr = date('YmdHis');
		return $dateStr .'.'. sha1(self::$config['smtp_user'] . self::$config['smtp_pass'] . $dateStr) .'.'. self::$config['smtp_from'];
	}
	
	public function addHTML($body) {
		$this->sendHTML = true;
		$this->htmlBody .= $body ."\r\n";
	}
	
	public function addTXT($body) {
		$this->txtBody .= $body;
	}
	
	public function addAddressFooter($htmlAddr, $txtAddr) {
		$this->htmlFooter = "\r\n". $htmlAddr ."\r\n";
		$this->txtFooter = "\r\n". $txtAddr ."\r\n";
	}
	
	public function addAttachment($name, $type, $file) {
		// $file can be a file location or the file itself
		$this->attachments[] = array('name' => $name, 'type' => $type, 'file' => $file);
	}
	
	private function prepare() {
		// Prepare an email to be sent
		
		if (!isset($this->headers['Date'])) throw new \Exception('Headers not set for email');
		
		$this->mime = new Mail_mime();
			
		if ($this->sendHTML) {
			
			$this->mime->setTXTBody($this->txtBody);
			$this->mime->setHTMLBody('<html><head><title>'. html($this->headers['Subject']) .'</title></head><body>'.$this->htmlBody.'</body></html>');
			
		} else {
			
			$this->mime->setTXTBody($this->txtBody);
			
		}
		
		foreach ($this->attachments as $thisAttach) {
			
			if (strlen($thisAttach['file']) > 100) $isFileName = false;
			elseif (file_exists($isFileName)) $isFileName = true;
			else $isFileName = false;
			
			if (!$thisAttach['type'] || !strlen($thisAttach['type'])) 
				$thisAttach['type'] = 'application/octet-stream';
				
			$this->mime->addAttachment($thisAttach['file'], $thisAttach['type'], $thisAttach['name'], $isFileName);
			
		}
		
		if ($this->emailUTF8) {
			
			if ($this->sendHTML)
				$body = $this->mime->get(array('html_charset' => 'utf-8', 'text_charset' => 'utf-8'));
			else
				$body = $this->mime->get(array('text_charset' => 'utf-8'));
			
		} else $body = $this->mime->get();
		
		return $body;
		
	}
	
	public function queue() {
		
		$body = $this->prepare();
		
		// TODO 
		
		return $newId;
		
	}
	
	public function send() {
		
		$body = $this->prepare();
		
		$smtp = Mail::factory('smtp', array(	
			'host' => self::$config['smtp_server'],
	  	'port' => self::$config['smtp_port'],
	    'auth' => true,
	    'username' => self::$config['smtp_user'],
	    'password' => self::$config['smtp_pass']
	  ));
	  
	  $to = explode(',', $this->headers['To']);
	  
	  if (strlen($this->headers['Cc'])) 
	  	$to = array_merge($to, explode(',', $this->headers['Cc']));
	  	
	  if ($this->bcc) 
	  	$to = array_merge($to, explode(',', $this->bcc));
	  	
	  if (strlen(self::$config['bcc'])) 
	  	$to = array_merge($to, explode(',', self::$config['bcc']));
	  
	  $mail = $smtp->send(implode(', ', $to), $this->mime->headers($this->headers), $body);
	  
	  if (PEAR::isError($mail)) {
	  	
	  	throw new \Exception('Error sending email: '. $mail->getMessage());
			return false;
			
		}
		
		return true;
	}
	
	public function queueAndSend() {
		
		$id = $this->queue();
		
		$this->processQueue($id);
		
	}
	
	static public function processQueue($id = false) {
		// Process all emails in queue (or a single email specified by $id)
		
		// TODO 
		
	}
	
	static public function cleanQueue($days) {
		// Remove all emails that were sent {$days} days ago
		
		// TODO 
		
	}
	
	static public install() {
		// One-time install
		// Create the supporting tables in the db for queueing
		
		// TODO 
		
		$sql = "CREATE TABLE IF NOT EXISTS `". self::$config['database']['tablename'] ."` (
			  `id` int(10) UNSIGNED NOT NULL auto_increment,
			  `level` int(5) UNSIGNED,
			  `entry` text NOT NULL,
			  `source` text NOT NULL,
			  `user` text NOT NULL,
			  `remoteip` tinytext NOT NULL,
			  `context` text NOT NULL,
			  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  KEY `id` (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;";
			
		$q = new Query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed!');
		
	}
		
}