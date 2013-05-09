<?php

namespace Firelit;

class EmailSender {
	
	private static $config = array(
		'smtp_server' => 'localhost',
		'smtp_port' => '25',
		'smtp_user' => '',
		'smtp_pass' => '',
		'smtp_from' => '', // If set will overwrite outgoing 'From' field
		'smtp_bcc' => '' // If set will be BCC'd on all SMTP-sent emails
	);
	
	public function __construct() {
	
		require_once('Mail.php'); // For new PEAR mailer
		require_once('Mail/mime.php'); // For new HTML emails 
		
	}
	
	public static config($config) {
		self::$config = array_merge($config, self::$config);
	}
	
	public static function send(Email $email) {
		
		if (strlen(self::$config['smtp_from'])) 
			$email->from = self::$config['smtp_from'];
		
		$mime = new Mail_mime();
		
		$mime->setTXTBody($email->text);
		
		if (strpos('<head>', $email->html) === false)
			$email->html = '<html><head><title>'. Strings::html($email->subject) .'</title></head><body>'. $email->html .'</body></html>';
			
		$mime->setHTMLBody($email->html);
		
		foreach ($email->attachments as $thisAttach) {
			
			if (strlen($thisAttach['file']) > 100) $isFileName = false;
			elseif (file_exists($isFileName)) $isFileName = true;
			else $isFileName = false;
			
			if (!$thisAttach['type'] || !strlen($thisAttach['type'])) 
				$thisAttach['type'] = 'application/octet-stream';
				
			$mime->addAttachment($thisAttach['file'], $thisAttach['type'], $thisAttach['name'], $isFileName);
			
		}
		
		$body = $mime->get(array('html_charset' => 'utf-8', 'text_charset' => 'utf-8'));
		
		$headers = $mime->headers(array(
			'From' => $email->from,
			'To' => $email->to,
			'Cc' => $email->cc,
			'Reply-To' => $email->replyTo,
			'Subject' => $email->subject,
			'Date' => (strlen($email->date) ? $email->date : date('r')),
			'Message-ID' => $email->messageId
		));
		
		$smtp = Mail::factory('smtp', array(	
			'host' => self::$config['smtp_server'],
			'port' => self::$config['smtp_port'],
			'auth' => true,
			'username' => self::$config['smtp_user'],
			'password' => self::$config['smtp_pass']
		));
		
		$masterTo = '';
		
		if (strlen($email->to)) {
			if (strlen($masterTo)) $masterTo .= ', ';
			$masterTo .= $email->to;
		}
		
		if (strlen($email->cc)) {
			if (strlen($masterTo)) $masterTo .= ', ';
			$masterTo .= $email->cc;
		}
		
		if (strlen($email->bcc)) {
			if (strlen($masterTo)) $masterTo .= ', ';
			$masterTo .= $email->bcc;
		}
		
		if (strlen(self::$config['smtp_bcc'])) {
			if (strlen($masterTo)) $masterTo .= ', ';
			$masterTo .= self::$config['smtp_bcc'];
		}
		
		if (!strlen($masterTo))
			throw new \Exception('No one to send this email to.');
			
		$mail = $smtp->send($masterTo, $headers, $body);
		
		if (PEAR::isError($mail))
			throw new \Exception('Error sending email: '. $mail->getMessage());
		
	}
	
}