<?PHP

class SessionTest extends PHPUnit_Framework_TestCase {
	
	private $s, $testVal;
	
	protected function setUp() {
		
		// TODO - Clean up? Remove database?
		Firelit\Session::install();
		
		$s = new Firelit\Session($_GLOBAL['TestConfig']::$Session);
		$testVal = mt_rand(1, 100000);
	}

	protected function testSetGet() {
		$s->test = $testVal;
		$this->assertEqual($s->test, $testVal);
	}
}