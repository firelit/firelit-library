Firelit-Library
===============

Firelit's standard PHP library provides a set of helpful classes for developing a website. The are created so that they can easily be used with an auto-loader, following the [PSR-0 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md).

Classes Included
----------------

### ApiResponse

A response-handling class for API end-points. Can handle all HTTP response codes and JSON & (limited) XML. Set a template to ensure some fields are always sent back with the response.

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

### DB

A database interaction class. Makes database connection management and SQL authoring slightly easier. 

Example usage:
```php
<?php

// One-time connection setup
Firelit\Query::connect(array(
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

### Email

### Encrypt

### HttpRequest

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
	// Something that throws an exception
} catch (Exception $e) {
	new Firelit\LogEntry(4, $e);
}
```

Please remember to restrict access (eg, via .htaccess) to any files you may be using for logging.

### Session

Session management class which can use PHP's native session features or a database. You can get and set any property name to the session object and it is dynamically saved (using magic getter and setter methods). The abstract method SessionStore defines how the session system stores and retrieves the data. This library provides database and PHP versions of the SessionStore class. Roll your own by extending SessionStore and use a class of this object when instantiating the Session object.

Note that if you are using PHP's native session support (which is an option), the expiration of a session is controlled by the `session.gc_maxlifetime` parameter.

Example usage:
```php
<?php

$store = Firelit\SessionStore:init('DB');
$sess = new Firelit\Session($store);

$sess->loggedIn = true;
$sess->userName = 'Peter';

echo '<p>Hello '. $sess->userName .'</p>';

$sess->destroy();
```

### User

### Vars

### Visitor

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