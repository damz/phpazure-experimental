<?php
if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_BlobStreamTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'Microsoft/Azure/Storage/Blob.php';

class Microsoft_Azure_BlobStreamTest extends PHPUnit_Framework_TestCase {
	static $path;
	
	protected static $uniqId = 0;
	
	protected $_tempContainers = array ();
	
	public function __construct() {
		require_once 'TestConfiguration.php';
		self::$path = dirname ( __FILE__ ) . '/datas/';
		self::$uniqId = mt_rand ( 0, 10000 );
	}
	
	protected function setUp() {
	
	}
	
	protected function tearDown() {
		if (count ( $this->_tempContainers ) > 0) {
			$storageClient = $this->_createStorageClient ();
			foreach ( $this->_tempContainers as $container )
				try {
					$storageClient->deleteContainer ( $container );
				} catch ( Exception $e ) {
					// ignore
				}
			
			$this->_tempContainers = array ();
		}
	}
	
	protected function getContainerName() {
		self::$uniqId ++;
		$name = "qa-test-container-" . self::$uniqId;
		$this->_tempContainers [] = $name;
		return $name;
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_BlobStreamTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	private function _createStorageClient() {
		return new Microsoft_Azure_Storage_Blob ( BLOB_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN ( 10, 250 ) );
	}
	
	private function _createTempFile($content) {
		$fileName = tempnam ( '', 'tst' );
		$fp = fopen ( $fileName, 'w' );
		fwrite ( $fp, $content );
		fclose ( $fp );
		
		return $fileName;
	}
	
	private function _expectException($e, $message) {
		$this->assertNotNull ( $e, $message );
		$this->assertEquals ( $message, $e->getMessage () );
	}
	
	/**
	 * Test read file
	 */
	public function testReadFile() {
		
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/stream/test.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, '/stream/test.txt', self::$path . "simple_blob.txt" );
		$fileContent = file_get_contents ( self::$path . "simple_blob.txt" );
		//		$fh = fopen ( $fileName, 'w' );
		//		fwrite ( $fh, $fileContent );
		//		fclose ( $fh );
		

		// read whole file using file_get_contents
		$result = file_get_contents ( $fileName );
		$this->assertEquals ( $fileContent, $result );
		
		// read line by line
		$fh = fopen ( $fileName, 'r' );
		$lines = explode ( "\n", $fileContent );
		foreach ( $lines as $line ) {
			$result = fgets ( $fh );
			$this->assertEquals ( $line, str_replace ( "\n", "", $result ) );
		}
		fclose ( $fh );
		
		// read lines using file
		$fileLines = file ( $fileName );
		$this->assertEquals ( count ( $lines ), count ( $fileLines ) );
		for($i = 0; $i < count ( $lines ); $i ++) {
			$this->assertEquals ( $lines [$i], str_replace ( "\n", "", $fileLines [$i] ) );
		}
		
		// read the whole file using fread
		$fh = fopen ( $fileName, 'r' );
		$result = fread ( $fh, 1000 );
		fclose ( $fh );
		$this->assertEquals ( $fileContent, $result );
		
		$storageClient->unregisterStreamWrapper ();
	}
	
	public function testReadFile_NotExists() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		$containerName = $this->getContainerName ();
		try {
			$fileName = 'azure://' . $containerName . '/test.txt';
			file_get_contents ( $fileName );
		} catch ( Exception $e ) {
			$this->assertEquals ( "The specified container does not exist.", $e->getMessage () );
		}
		
		$storageClient->createContainer ( $containerName );
		
		try {
			// missing blob name
			$fileName = 'azure://' . $containerName;
			file_get_contents ( $fileName );
		} catch ( Exception $e ) {
			$this->assertEquals ( "The specified blob does not exist.", $e->getMessage () );
		}
		
		try {
			$fileName = 'azure://' . $containerName . '/test.txt';
			file_get_contents ( $fileName );
		} catch ( Exception $e ) {
			$this->assertEquals ( "The specified blob does not exist.", $e->getMessage () );
		}
		
		$storageClient->unregisterStreamWrapper ();
	}
	
	/**
	 * Test write file
	 */
	public function testWriteFile() {
		$fileContent = "test write file";
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/testread.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		file_put_contents ( $fileName, $fileContent );
		
		$this->assertEquals ( $fileContent, file_get_contents ( $fileName ) );
		
		// append file content
		$newContent = "\nWindows Azure";
		$fh = fopen ( $fileName, 'a+' );
		fwrite ( $fh, $newContent );
		fclose ( $fh );
		
		$this->assertEquals ( $fileContent . $newContent, file_get_contents ( $fileName ) );
		
		// replace file content
		$fh = fopen ( $fileName, 'w+' );
		fwrite ( $fh, $newContent );
		fclose ( $fh );
		
		$this->assertEquals ( $newContent, file_get_contents ( $fileName ) );
		
		// test fputs
		$fh = fopen ( $fileName, 'w' );
		$content = array ("line 1", "line 2", "line 3" );
		foreach ( $content as $line )
			fputs ( $fh, $line );
		fclose ( $fh );
		
		$localFile = tempnam ( '', 'tst' );
		$storageClient->getBlob ( $containerName, '/testread.txt', $localFile );
		$this->assertEquals ( implode ( "", $content ), file_get_contents ( $localFile ) );
		
		unlink ( $localFile );
		$storageClient->unregisterStreamWrapper ();
	
	}
	
	public function testWriteFile_Invalid() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		$invalidNames = array ("a", "aa", "test-1-con tainer", "test-1--container", "CONTAINER", "test#-1-container" );
		foreach ( $invalidNames as $containerName ) {
			$exceptionThrown = false;
			try {
				$fileName = 'azure://' . $containerName;
				file_put_contents ( $fileName, "testing" );
			} catch ( Exception $e ) {
				$exceptionThrown = true;
			}
			$this->assertTrue ( $exceptionThrown );
		
		}
		
		$storageClient->unregisterStreamWrapper ();
	}
	
	/**
	 * Test unlink file
	 */
	public function testUnlinkFile() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/test.txt';
		$exception = null;
		try {
			unlink ( $fileName );
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->_expectException ( $exception, "The specified container does not exist." );
		
		file_put_contents ( $fileName, "test unlink file" );
		unlink ( $fileName );
		
		$result = $storageClient->listBlobs ( $containerName );
		$this->assertEquals ( 0, count ( $result ) );
		
		$exception = null;
		try {
			// delete file twice
			unlink ( $fileName );
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->_expectException ( $exception, "The specified blob does not exist." );
		$storageClient->unregisterStreamWrapper ();
	}
	
	/**
	 * Mkdir operation mock
	 *
	 */
	public function testMkdir() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$containerName = $this->getContainerName ();
		try {
			mkdir ( 'azure://' . $containerName );
			$resultContainer = $storageClient->getContainer ( $containerName );
			$this->assertNotNull ( $resultContainer );
			$this->assertTrue ( sizeof ( $resultContainer ) == 1 );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testMkdirMultiLevel() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$containerName = $this->getContainerName ();
		
		$thrownException = false;
		try {
			mkdir ( 'azure://' . $containerName . "/" . "childDir" );
		} catch ( Exception $e ) {
			$thrownException = true;
		}
		
		$this->assertTrue ( $thrownException ); // Maybe prompt user "Not allow path like a/b/c ..."
	}
	
	/**
	 * Rmdir operation mock
	 *
	 */
	public function testRmdir() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$containerName = $this->getContainerName ();
		try {
			$storageClient->createContainer ( $containerName );
			$resultContainer = $storageClient->getContainer ( $containerName );
			$this->assertNotNull ( $resultContainer );
			$this->assertTrue ( sizeof ( $resultContainer ) == 1 );
			
			// Rmdir
			rmdir ( 'azure://' . $containerName );
			$resultContainer = $storageClient->getContainer ( $containerName );
		} catch ( Exception $e ) {
			$this->assertNotNull ( $e );
			$this->assertEquals ( "The specified container does not exist.", $e->getMessage () );
		}
	}
	
	/**
	 * ListDir operation mock. List a dir means list blobs in a container
	 *
	 */
	public function testOpenDir() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$containerName = $this->getContainerName ();
		
		$content = "I love hello ketty.";
		$blobName = "blobfile";
		
		//prepare resources
		mkdir ( 'azure://' . $containerName );
		for($i = 0; $i < 10; $i ++) {
			file_put_contents ( 'azure://' . $containerName . "/" . $blobName . $i, $content );
		}
		
		$blobs = $storageClient->listBlobs ( $containerName );
		$this->assertEquals ( 10, sizeof ( $blobs ) );
		
		// test assert
		$dh = opendir ( 'azure://' . $containerName );
		
		// Fetch blob as they are children of the directory
		while ( ($file_as_blob = readdir ( $dh )) !== false ) {
			$this->assertEquals ( $content, file_get_contents ( 'azure://' . $containerName . "/" . $file_as_blob ) );
		}
		closedir ( $dh );
	}

}

// Call Microsoft_Azure_BlobStreamTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_BlobStreamTest::main") {
	Microsoft_Azure_BlobStreamTest::main ();
}

?>