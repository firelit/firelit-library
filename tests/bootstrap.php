<?PHP


function bootstrapAutoloader($class) {
	$parts = split('/', $class);
	require_once('../'. $parts[1] .'.php');
}

spl_autoload_register('bootstrapAutoloader');

class TestConfig extends Firelit\Config { }