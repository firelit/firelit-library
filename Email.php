<?PHP

namespace Firelit;

class Email {
	
	public $from = '';
	public $to = '';
	public $cc = '';
	public $bcc = '';
	public $replyTo = '';
	
	public $subject = '';
	
	public $date = '';
	public $messageId = '';
	
	public $html = '';
	public $text = '';
		
	public $attachments = array();
	
	public function __construct() {
		
		$this->messageId = $this->createMessageId();
		
	}
	
	public function __clone() {
		
		$this->messageId = $this->createMessageId();
		
	}
	
	private function createMessageId() {
	
		$dateStr = date('YmdHis');
		return $dateStr .'.'. sha1($_SERVER['HOST_NAME'] . mt_rand(0, 10000) . microtime()) .'@message.id';
		
	}
	
	public function updateTimestamp() {
		
		// If this method is not called, the email date be set by the EmailSender class
		$this->date = date('r');
		
	}
	
	private function addAddress($addName, $addEmail, &$destination) {
		
		$addName = trim($addName);
		$addEmail = trim($addEmail);
		
		if (strlen($addName)) $addName = '"'. str_replace('"', '', $addName) .'" ';
		
		if (!Strings::validEmail($addEmail)) 
			throw new \Exception('Invalid email address submitted: '. $addEmail);
		
		$addEmail = $addName . Strings::lower($addEmail);
		
		if (strlen($destination)) $destination .= ', ';
		$destination .= $addEmail;
		
	}
	
	public function addTo($name, $email) {
		
		$this->addAddress($name, $email, $this->to);
		
	}
	
	public function addCc($name, $email) {
		
		$this->addAddress($name, $email, $this->Cc);
		
	}
	
	public function addBcc($name, $email) {
		
		$this->addAddress($name, $email, $this->Bcc);
		
	}
	
	public function addHtml($body) {
	
		$this->htmlBody .= $body;
		
	}
	
	public function addText($body) {
	
		$this->txtBody .= $body;
		
	}
	
	public function addAttachment($name, $type, $file) {
		// $file can be a file location or the file itself
		$this->attachments[] = array('name' => $name, 'type' => $type, 'file' => $file);
		
	}
		
}