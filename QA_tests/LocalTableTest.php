<?php

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_LocalTableTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'TableTest.php';

require_once 'Microsoft/Azure/Storage/Table.php';

class Microsoft_Azure_LocalTableTest extends Microsoft_Azure_TableTest {
	public function __construct() {
		require_once 'LocalTestConfiguration.php';
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_LocalTableTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function setUp() {
		
	}

}

// Call Microsoft_Azure_LocalTableTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_LocalTableTest::main") {
	Microsoft_Azure_LocalBlobTest::main ();
}

?>