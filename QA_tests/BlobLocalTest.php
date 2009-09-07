<?php

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_BlobLocalTest::main' );
}

require_once 'PHPUnit/Framework.php';

require 'BlobTest.php';

/** Microsoft_WindowsAzure_Storage_Blob */
require_once 'Microsoft/WindowsAzure/Storage/Blob.php';

class Microsoft_WindowsAzure_BlobLocalTest extends Microsoft_WindowsAzure_BlobTest {
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_WindowsAzure_BlobLocalTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function setUp() {
		require_once 'LocalTestConfiguration.php';
	}

}

// Call Microsoft_WindowsAzure_BlobStorageTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_WindowsAzure_BlobLocalTest::main") {
	Microsoft_WindowsAzure_BlobLocalTest::main ();
}

?>