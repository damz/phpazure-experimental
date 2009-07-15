<?php

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_BlobLocalTest::main' );
}

require_once 'PHPUnit/Framework.php';

require 'BlobTest.php';

/** Microsoft_Azure_Storage_Blob */
require_once 'Microsoft/Azure/Storage/Blob.php';

class Microsoft_Azure_BlobLocalTest extends Microsoft_Azure_BlobTest {
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_BlobLocalTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function setUp() {
		require_once 'LocalTestConfiguration.php';
	}

}

// Call Microsoft_Azure_BlobStorageTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_BlobLocalTest::main") {
	Microsoft_Azure_BlobLocalTest::main ();
}

?>