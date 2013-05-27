<?PHP

class QueryTest extends PHPUnit_Framework_TestCase {
	
	protected $q, $res;
	
	protected function setUp() {
		
		Firelit\Query::config(array(
			'type' => 'other',
			'dsn' => 'sqlite::memory'
		));
		
		$this->q = new Firelit\Query();
		$this->res = $this->q->query("CREATE TABLE IF NOT EXISTS `Tester` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `name` TINYTEXT, `date` DATETIME, `state` BOOL)");
		
	}
	
	public function testConstructor() {
		
		// If setup() worked, then this will pass!
		$this->assertTrue($this->res);
		
	}
	
	public function testInsert() {
		
		$this->q->insert('Tester', array(
			'name' => 'John',
			'date' => array('SQL', "DATETIME('now')"),
			'state' => false
		));
		
		$this->assertTrue( $q->success() );
		
		
	}
	
	public function testReplace() {
		
		$this->q->replace('Tester', array(
			'name' => 'Sally',
			'date' => array('SQL', "DATETIME('now')"),
			'state' => false
		));
		
		$this->assertTrue( $q->success() );
		
		
	}
	
	protected function tearDown() {
		
		$this->q->query("DROP TABLE `Tester`");
		
	}
	
}