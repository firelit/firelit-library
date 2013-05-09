<?PHP

class SessionTest extends PHPUnit_Framework_TestCase {
	
	private $session, $store, $testVal;
	
	protected function setUp() {
		
		$this->testVal = array(
			'Array of random variables',
			mt_rand(1, 100000000),
			mt_rand(1, 100000000),
			mt_rand(1, 100000000)
		);
		
		$this->store = Firelit\SessionStore::init('Mem');
		$this->session = new Firelit\Session($this->store);
		
	}
		
	public function testSetGet() {
	
		$varName = 'test'. mt_rand(0, 1000);
		$this->session->$varName = $this->testVal;
		
		$this->session->save();
		$this->session->flushCache();
		
		$this->assertEquals($this->session->$varName, $this->testVal);
		
	}
		
	public function testUnset() {
	
		$varName = 'test'. mt_rand(0, 1000);
		$this->session->$varName = $this->testVal;
		
		unset($this->session->$varName);
		
		$this->session->save();
		$this->session->flushCache();
		
		$this->assertNull($this->session->$varName);
		
	}
	
	
	public function testDestroy() {
	
		$varName = 'test'. mt_rand(0, 1000);
		$this->session->$varName = $this->testVal;
		
		$this->session->destroy();
		
		$this->assertNull($this->session->$varName);
		
	}
}