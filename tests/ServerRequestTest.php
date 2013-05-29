<?PHP

class ServerRequestTest extends PHPUnit_Framework_TestCase {
	
	public function testConstructor() {
		
		$_POST = $orig = array(
			'test' => true,
			'tester' => array(
				0 => 'value0',
				1 => 'value1'
			)
		);
		
		$_GET = array();
		$_COOKIE = array();
		
		$sr = new Firelit\ServerRequest();
		
		$this->assertEquals( $orig, $sr->post, '$_POST should be copied into internal property.' );
		
	}
	
	public function testUnset() {
		
		$_POST = $orig = array(
			'test' => true,
			'tester' => array(
				0 => 'value0',
				1 => 'value1'
			)
		);
		
		$_GET = array();
		$_COOKIE = array();
		
		$sr = new Firelit\ServerRequest(function(&$val) {
			Firelit\Strings::cleanUTF8($val);
		});
		
		$this->assertEquals( $orig, $sr->post, '$_POST was not copied by ServerRequest object.' );
		$this->assertNull( $_POST, '$_POST was not set to null by ServerRequest object.' );
		
	}
	
	public function testFilter() {
		
		$_POST = array();
		
		$_GET = $orig = array(
			'nested' => array(
				'deep' => array(
					'deeper' => 'bad',
					'other' => 'good'
				)
			),
			'shallow' => 'bad'
		);
			
		$_COOKIE = array();
		
		$sr = new Firelit\ServerRequest(function(&$val) {
			if ($val == 'bad') $val = 'clean';
		});
		
		$this->assertNotEquals( $orig, $sr->get, '$_GET value remains unchanged.' );
		$this->assertEquals( 'clean', $sr->get['nested']['deep']['deeper'], 'Deep array value not cleaned.' );
		$this->assertEquals( 'good', $sr->get['nested']['deep']['other'], 'Deep array value mistakenly cleaned.' );
		$this->assertEquals( 'clean', $sr->get['shallow'], 'Shallow array value not cleaned.' );
		
	}
}