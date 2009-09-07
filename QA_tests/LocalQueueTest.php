<?php

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_LocalQueueTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'QueueTest.php';

require_once 'Microsoft/WindowsAzure/Storage/Queue.php';

class Microsoft_WindowsAzure_LocalQueueTest extends Microsoft_WindowsAzure_QueueTest {
	public function __construct() {
		require_once 'LocalTestConfiguration.php';
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_WindowsAzure_LocalQueueTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function setUp() {
		
	}

}

// Call Microsoft_WindowsAzure_LocalBlobTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_WindowsAzure_LocalQueueTest::main") {
	Microsoft_WindowsAzure_LocalQueueTest::main ();
}

?>