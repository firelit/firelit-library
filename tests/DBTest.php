<?PHP

class DBTest extends PHPUnit_Framework_TestCase {
	
	private $db;
	
	protected function setUp() {
		$db = new Firelit\DB();
	}

}