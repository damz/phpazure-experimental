<?php
if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_SessionTest::main' );
}

require_once 'PHPUnit/Framework.php';

/** Microsoft_Azure_SessionHandler */
require_once 'Microsoft/Azure/SessionHandler.php';

/** Microsoft_Azure_Storage_Table */
require_once 'Microsoft/Azure/Storage/Table.php';

class Microsoft_Azure_SessionTest extends PHPUnit_Framework_TestCase {
	
	protected static $uniqId = 0;
	
	protected static $tablePrefix = "phpsessiontest";
	
	protected $_tempTables = array ();
	
	public function __construct() {
		require_once 'TestConfiguration.php';
		self::$uniqId = mt_rand ( 0, 10000 );
	}
	
	protected function setUp() {
	
	}
	
	protected function tearDown() {
		if (count ( $this->_tempTables ) > 0) {
			$storageClient = $this->_createStorageClient ();
			foreach ( $this->_tempTables as $table )
				try {
					$storageClient->deleteTable ( $table );
				} catch ( Exception $e ) {
					// ignore
				}
			
			$this->_tempTables = array ();
		}
	}
	
	protected function session_id() {
		self::$uniqId ++;
		return md5 ( self::$uniqId );
	}
	
	protected function generateTableName() {
		self::$uniqId ++;
		$name = self::$tablePrefix . self::$uniqId;
		$this->_tempTables [] = $name;
		return $name;
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_SessionTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function _createStorageClient() {
		return new Microsoft_Azure_Storage_Table ( TABLE_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN ( 10, 250 ) );
	}
	
	protected function _createSessionHandler($storageInstance, $tableName) {
		$sessionHandler = new Microsoft_Azure_SessionHandler ( $storageInstance, $tableName );
		return $sessionHandler;
	}
	
	/**
	 * Test destroy
	 */
	public function testDestroy() {
		$storageClient = $this->_createStorageClient ();
		$tableName = $this->generateTableName ();
		$sessionHandler = $this->_createSessionHandler ( $storageClient, $tableName );
		$sessionHandler->open ();
		
		$sessionId = $this->session_id ();
		
		$data = array ("comment" => "session test", "mode" => "dev", "count" => 1, "test" => true, "price" => 99.9 );
		$sessionHandler->write ( $sessionId, serialize ( $data ) );
		
		$result = $sessionHandler->destroy ( $sessionId );
		$this->assertTrue ( $result );
		
		$verifyResult = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( 0, count ( $verifyResult ) );
		
		// destroy session again 
		$sessionHandler->destroy ( $sessionId );
		$verifyResult = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( 0, count ( $verifyResult ) );
		
		// destroy not exist session
		$sessionId = $this->session_id ();
		$result = $sessionHandler->destroy ( $sessionId );
		$this->assertFalse ( $result );
	}
	
	/**
	 * Test gc
	 */
	public function testGc() {
		$storageClient = $this->_createStorageClient ();
		$tableName = $this->generateTableName ();
		$sessionHandler = $this->_createSessionHandler ( $storageClient, $tableName );
		$sessionHandler->open ();
		
		$result = $sessionHandler->gc ( 0 );
		$this->assertTrue ( $result );
		
		$sessionId = $this->session_id ();
		
		$data = array ("comment" => "session test", "mode" => "dev", "count" => 1, "test" => true, "price" => 99.9 );
		$sessionHandler->write ( $sessionId, serialize ( $data ) );
		
		sleep ( 1 );
		$result = $sessionHandler->gc ( 0 );
		$this->assertTrue ( $result );
		$verifyResult = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( 0, count ( $verifyResult ) );
	}

}

// Call Microsoft_Azure_SessionTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_SessionTest::main") {
	Microsoft_Azure_SessionTest::main ();
}

?>