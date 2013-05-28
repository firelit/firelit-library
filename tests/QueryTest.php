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
	
	public function testQuery() {
		
		// If setup() worked (and the query used there), then this will pass!
		$this->assertTrue($this->res);
		
	}
	
	public function testInsert() {
		
		$this->q->insert('Tester', array(
			'name' => 'John',
			'date' => array('SQL', "DATETIME('now')"),
			'state' => false
		));
		
		$this->assertTrue( $this->q->success() );
		
		
	}
	
	public function testReplace() {
		
		$this->q->replace('Tester', array(
			'name' => 'Sally',
			'date' => array('SQL', "DATETIME('now')"),
			'state' => true
		));
		
		$this->assertTrue( $this->q->success() );
		
		
	}
	
	/**
	 * @depends testReplace
	 */
	public function testSelect() {

		$this->q->insert('Tester', array(
			'name' => 'Sally',
			'date' => array('SQL', "DATETIME('now')"),
			'state' => true
		));

		$this->q->select('Tester', '*', '`name`=:name', array( 'name' => 'Sally' ), 1, 1);
		
		$this->assertTrue( $this->q->success() );

		$this->assertTrue( $this->q->getNumRows() === 1 );

// TODO
var_dump($this->q->getRow());
		
		$rows = $this->q->getAll();
		
		$this->assertTrue( sizeof($rows) === 1 );
		
		$this->assertEquals( $rows[0]['name'] == 'Sally');
		
	}
	
	protected function tearDown() {
		
		$this->q->query("DROP TABLE `Tester`");
		
	}
	
}