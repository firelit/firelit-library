Firelit-Library
===============

Firelit's standard PHP library provides a set of helpful classes for developing a website. The are created so that they can easily be used with an auto-loader, following the [PSR-0 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md).

Requirements
------------

- PHP version 5.4.0 and higher
- MultiByte PHP extension
- cURL PHP extension (required for `HttpRequest` class)
- PDO PHP extension (required for `Query` class)
- PEAR Mail and Mail mime extensions (required for `EmailSenderSMTP` class)

Classes Included
----------------

### ApiResponse

A response-handling class for API end-points. Can handle all HTTP response codes and JSON & _limited_ XML. Set a template to ensure some fields are always sent back with the response.

Example usage:
```php
<?php

$resp = ApiResponse::init('JSON');

$resp->setTemplate(array(
	'success' => false,
	'message' => ''
));

$resp->code(404);

$resp->respondAndEnd(array(
	'message' => 'Resource could not be located.'
));
```

### CheckHTTPS

A short class to help verify a connection is secure (ie, using TLS) and take action (ie, redirect or return error) if it isn't. It would be best if this were done via the web server settings but this isn't always possible.

Example usage:
```php
<?php

// Simple redirect if the connection isn't secure
Firelit\CheckHTTPS::redirect();

// Or log the insecure access attempt and exit with HTTP 400
Firelit\CheckHTTPS::error(function() {
	new Firelit\LogEntry(1, 'Connection is not secure and it needs to be.', __FILE__, __LINE__);
});
```

### Crypto

A symmetrical-key encryption/decryption helper class (uses MCRYPT_RIJNDAEL_256 aka AES). Includes password hashing method.

Example encryption/decryption usage:
```php
<?php

$mySecretPassword = 'Super secret!';

$iv = Firelit\Crypto::getIv();
$encrypted = Firelit\Crypto::encrypt('Super secret text', $mySecretPassword, $iv);

$decrypted = Firelit\Crypto::decrypt($encrypted, $mySecretPassword, $iv);
```

Example password hasing usage:
```php
<?php

// Getting a hash for a new password
list($hash, $salt) = Firelit\Crypto::password($_POST['new_password']);

// Checking a submitted password against a user's stored info
list($hash, $salt) = Firelit\Crypto::password($_POST['password'], $user['salt']);
if ($user['hash'] == $hash) echo 'Password is correct!';
```

### Email

An email to properly form and send emails with MIME multi-part, database-based queueing and SMTP support.

Two email handling classes are included: EmailSender and EmailStore. See example usage below. Roll your own email sender and email store by extending these abstract classes.

Example usage:
```php
<?php

Firelit\EmailSenderSMTP::config(array(
	'smtp_host' => 'localhost',
	'smtp_port' => 25,
	'smtp_user' => 'example',
	'smtp_pass' => 'elpmaxe'
));

Firelit\EmailStoreDB::config(array(
	'tableName' => 'EmailQueue'
));

$email = new Firelit\Email();

$email->addTo('Jim Bo', 'jimbo@firelit.com'); // Add name & email addresses
$email->to .= ', noname@firelit.com'; // Or set the to, cc & bcc field explicity through their properties

$email->addCc('Accounting', 'accounting@firelit.com');
$email->addBcc('Compliance', 'compliance@firelit.com');

$email->subject = 'An important email';

$email->html = '<h1>Email Test!</h1>'; // Set the html part of the email
$email->text = '*Email Test!*'; // Set the text part of the email

if ($storeAndSend) {
	// You can use the EmailStore object to manage a db-based email queue
	$store = Firelit\EmailStore::init('DB');
	$store->storeAndSend( $email, Firelit\EmailSender::init('SMTP') ); // Store in DB and then try sending
} elseif ($noStoreJustSend) {
	// You can use the EmailSender class to send it out immediately
	$sender = Firelit\EmailSender::init('SMTP');
	$sender->send($email);
}
```

### EndWithError

A class to terminate the script, set the error code and display an error page.

Example usage:
```php
<?php

// Optionally create an HTML page to display
$body = Firelit\EndWithError:createErrorBody('Page Not Found', 'Page Not Found', 'Sorry, we could not find the page you were looking for.');

Firelit\EndWithError::now(404, $body);
```

### HttpRequest

A class to manage new HTTP requests to external web services and websites. Includes file-based cookie support.

Example usage:
```php
<?php

Firelit\HttpRequest::config(array(
	'userAgent' => 'My little user agent string'
));

$http = new Firelit\HttpRequest();

$http->enableCookies();

// 'get', 'post' and 'other' (for put, delete, etc) are also available methods
$http->get('http://www.google.com/');

// Hmmm, I wonder what cookies Google sets...
echo '<pre>'. file_get_contents($http->cookieFile) .'</pre>';
```

### LogEntry 

An application logging class for recording errors, events or other useful data to either a database table or a file. 

Example usage:
```php
<?php

// One-time logger setup
Firelit\LogEntry::config(array(
	'file' => array('enabled' => true)
));

// Standard textual/contextual log entry:
new Firelit\LogEntry(5, 'The website is going down!', __FILE__, __LINE__);

// Exceptions can be logged:
try {
	throw new \Exception('all your base are belong to us');
} catch (Exception $e) {
	new Firelit\LogEntry(4, $e);
}
```

Please remember to restrict access (eg, via .htaccess) to any files you may be using for logging.

### Query

A database interaction class and SQL query creator. Makes database connection management and SQL authoring slightly easier. 

Example usage:
```php
<?php

// One-time connection setup
Firelit\Query::config(array(
	'db_name' => 'database',
	'db_ip' => 'localhost',
	'db_user' => 'username',
	'db_pass' => 'password'
));

$q = new Firelit\Query();

$q->insert('TableName', array(
	/* columnName => value */
	'name' => $name,
	'state' => $state
));

if (!$q->success()) die('It did not work :(');

$q->query("SELECT * FROM `TableName` WHERE `name`=:name", array('name' => $name));

while ($row = $q->getRow()) 
	echo $row['name'] .': '. $row['state'] .'<br>';
```

### Session

Session management class which can use PHP's native session features or a database. You can get and set any property name to the session object and it is dynamically saved (using magic getter and setter methods). The abstract method SessionStore defines how the session system stores and retrieves the data. This library provides database and PHP versions of the SessionStore class. Roll your own by extending SessionStore and use a class of this object when instantiating the Session object. Sessions are saved all at once at session object `__destruct` or when the `save()` method is explicitly called.

Note that if you are using PHP's native session support (which is an option), the expiration of a session is controlled by the `session.gc_maxlifetime` parameter.

Example usage:
```php
<?php

$store = Firelit\SessionStore:init('DB', new Query);
$sess = new Firelit\Session($store);

$sess->loggedIn = true;
$sess->userName = 'Peter';

echo '<p>Hello '. $sess->userName .'</p>';
```

Note that if you're using a database as a session store that the expired values should be cleaned up regularly (eg, with a cron job) via the `SessionStoreDB->cleanExpired()` method.

### Strings

A set of string helper functions wrapped into a class.

Example usage:
```php
<?php

Firelit\Strings::cleanUTF8($_POST);
```

### User

*TODO:* A class for managing users. Comming soon...

### Vars

*TODO:* A class for managing application-level, persistent variables. Comming soon...

Auto-Loader Example
-------------------

The beauty of the auto-loader is that it will only load & parse PHP files that it needs. To use it, however, you must define an autoloader function. Here is one exmaple that could be used, created by the [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) Framework Interop Group:

```php
<?php

function autoload($className) {
	$className = ltrim($className, '\\');
	$fileName  = '';
	$namespace = '';
	if ($lastNsPos = strrpos($className, '\\')) {
	  $namespace = substr($className, 0, $lastNsPos);
	  $className = substr($className, $lastNsPos + 1);
	  $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	}
	$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	
	require $fileName;
}
```