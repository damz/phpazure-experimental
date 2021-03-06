<?php

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_LocalBlobTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'BlobTest.php';

require_once 'Microsoft/WindowsAzure/Storage/Blob.php';

class Microsoft_WindowsAzure_LocalBlobTest extends Microsoft_WindowsAzure_BlobTest {
	public function __construct() {
		require_once 'LocalTestConfiguration.php';
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_WindowsAzure_LocalBlobTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function setUp() {
		
	}

}

// Call Microsoft_WindowsAzure_LocalBlobTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_WindowsAzure_LocalBlobTest::main") {
	Microsoft_WindowsAzure_LocalBlobTest::main ();
}

?>