<?PHP

namespace 'Firelit';

include_once('library.php');

class Email {
	
	private $htmlBody = '';
	private $txtBody = '';
	
	private $htmlFooter = '';
	private $txtFooter = '';
	
	private $sendHTML = false;
	private $sendTo = '';
	private $headers = false;
	private $bcc = '';
	
	public $emailUTF8 = true;
	public $attachments = array();
	
	public function __construct($recipients) {
		if (!function_exists('logIt')) throw new Exception('logIt() function not defined.');
		if (!function_exists('html')) throw new Exception('html() function not defined.');
		
		$incs = get_included_files();
		if (!in_array('Mail.php', $incs)) require_once('Mail.php'); // For new PEAR mailer
		if (!in_array('Mail/mime.php', $incs)) require_once('Mail/mime.php'); // For new HTML emails 
		
		$this->sendTo = $recipients;
	}
	
	public function setHeader($subject, $fromName, $replyTo = '', $bccOnly = false) {
		$this->headers = array(
			'From' => '"'. $fromName.'" <'.SMTP_ADD.'>',
			'To' => ($bccOnly ? '' : $this->sendTo), // Blank for BCC
			'Reply-To' => $replyTo,
			'Date' => date('r'),
			'Subject' => $subject
		);
	}
	
	public function addBCC($email) {
		$this->bcc .= ', '. $email;
	}
	
	public function addHTML($body) {
		$this->sendHTML = true;
		$this->htmlBody .= $body ."\r\n";
	}
	
	public function addTXT($body) {
		$this->txtBody .= $body;
	}
	
	public function addAddressFooter($htmlAddr = false, $txtAddr = false) {
		if (!$htmlAddr) {
			
			$this->htmlFooter = "\r\n<p style=\"margin:1.12em 0;\">". DOM_NAME ."\r\n, ". MAIL_FOOTER_ADDR ."</p>\r\n";
			
			$this->txtFooter =  "\r\n". DOM_NAME ."\r\n". MAIL_FOOTER_ADDR ."\r\n";
			
		} else {
			
			$this->htmlFooter = "\r\n". $htmlAddr ."\r\n";
			
			$this->txtFooter = "\r\n". $txtAddr ."\r\n";
			
		}
	}
	
	public function addAttachment($name, $type, $file) {
		// $file can be a file location or the file itself
		$this->attachments[] = array('name' => $name, 'type' => $type, 'file' => $file);
	}
	
	public function send() {
		if (!$this->headers) {
			logIt('! Headers not set for email #email', __FILE__, __LINE__);
			return false;
		}
		
		$mime = new Mail_mime();
			
		if ($this->sendHTML) {
			
			$mime->setTXTBody($this->txtBody);
			$mime->setHTMLBody('<html><head><title>'. html($this->headers['Subject']) .'</title></head><body>'.$this->htmlBody.'</body></html>');
			
		} else {
			
			$mime->setTXTBody($this->txtBody);
			
		}
		
		foreach ($this->attachments as $thisAttach) {
			
			if (strlen($thisAttach['file']) > 100) $isFileName = false;
			elseif (file_exists($isFileName)) $isFileName = true;
			else $isFileName = false;
			
			if (!$thisAttach['type'] || !strlen($thisAttach['type'])) 
				$thisAttach['type'] = 'application/octet-stream';
				
			$mime->addAttachment($thisAttach['file'], $thisAttach['type'], $thisAttach['name'], $isFileName);
			
		}
		
		if ($this->emailUTF8) {
			
			if ($this->sendHTML)
				$body = $mime->get(array('html_charset' => 'utf-8', 'text_charset' => 'utf-8'));
			else
				$body = $mime->get(array('text_charset' => 'utf-8'));
			
		} else $body = $mime->get();
		
		$this->headers = $mime->headers($this->headers);	
			
		$smtp = Mail::factory('smtp', array(	
			'host' => SMTP_SERVER,
	  	'port' => SMTP_PORT,
	    'auth' => true,
	    'username' => SMTP_UN,
	    'password' => SMTP_PW));
	    
	  $mail = $smtp->send($this->sendTo . $this->bcc .', '. BCC_EMAIL, $this->headers, $body);
	  
	  if (PEAR::isError($mail)) {
			logIt('! Error send email to '. $this->sendTo .': '. $mail->getMessage() .' #email', __FILE__, __LINE__);
			return false;
		}
		
		return true;
	}
}