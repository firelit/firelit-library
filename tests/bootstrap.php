<?PHP

function bootstrapAutoloader($class) {
	$parts = explode('\\', $class);
	if ($parts[0] != 'Firelit') 
		throw new Exception('Invalid class for autoloading: '. $parts[0] .', '. $class);
	require_once('../'. $parts[1] .'.php');
}

spl_autoload_register('bootstrapAutoloader');

Firelit\Query::config(array(
	
));