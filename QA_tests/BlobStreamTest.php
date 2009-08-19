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
		$this->_assertExceptionMessageContain ( $e, $message );
	}
	
	/**
	 * Test read file
	 */
	public function testReadFile() {
		
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/test.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'test.txt', self::$path . "simple_blob.txt" );
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
			$this->_assertExceptionMessageContain ( $e, "The specified container does not exist." );
		}
		
		$storageClient->createContainer ( $containerName );
		
		try {
			// missing blob name
			$fileName = 'azure://' . $containerName;
			file_get_contents ( $fileName );
		} catch ( Exception $e ) {
			$this->_assertExceptionMessageContain ( $e, "The specified blob does not exist." );
		}
		
		try {
			$fileName = 'azure://' . $containerName . '/test.txt';
			file_get_contents ( $fileName );
		} catch ( Exception $e ) {
			$this->_assertExceptionMessageContain ( $e, "The specified blob does not exist." );
		}
		
		$storageClient->unregisterStreamWrapper ();
	}
	
	protected function _assertExceptionMessageContain($ex, $message) {
		$this->assertTrue ( strpos ( $ex->getMessage (), $message ) !== false );
	}
	//Test eof()
	public function testEndOfFile() {
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/test.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'test.txt', self::$path . "simple_blob.txt" );
		
		$fh = fopen ( $fileName, 'r' );
		fread ( $fh, 10 );
		
		$VerifyEnd = feof ( $fh );
		$this->assertFalse ( $VerifyEnd );
		
		//the length of blob content is 44.
		fread ( $fh, 34 );
		$VerifyEnd = feof ( $fh );
		$this->assertTrue ( $VerifyEnd );
		
		fclose ( $fh );
	}
	
	//Test tell
	public function testTell() {
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/test.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'test.txt', self::$path . "simple_blob.txt" );
		
		$fh = fopen ( $fileName, 'r' );
		
		$position = ftell ( $fh );
		$this->assertEquals ( 0, $position );
		
		fread ( $fh, 10 );
		$position = ftell ( $fh );
		$this->assertEquals ( 10, $position );
		
		fread ( $fh, 34 );
		$position = ftell ( $fh );
		$this->assertEquals ( 44, $position );
		
		fread ( $fh, 1 );
		$position = ftell ( $fh );
		$this->assertEquals ( 44, $position );
		
		fclose ( $fh );
	}
	
	//Test seek. when invoke fseek(), here all rerurn -1. operation failed.
	public function testSeek() {
		$fileContent = "test seek file";
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/testseek.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		//file_put_contents ( $fileName, $fileContent );
		$localFile = $this->_createTempFile ( $fileContent );
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, "testseek.txt", $localFile );
		
		$fh = fopen ( $fileName, 'r' );
		
		$curpos = ftell ( $fh );
		$this->assertEquals ( 0, $curpos );
		
		//whence: 0 = SEEK_SET, the head of file 
		fseek ( $fh, 5, SEEK_SET );
		$curpos = ftell ( $fh );
		$this->assertEquals ( 5, $curpos );
		
		//whence: 1 = SEEK_CUR, the current position of file
		fread ( $fh, 2 );
		fseek ( $fh, 1, SEEK_CUR );
		$curpos = ftell ( $fh );
		$this->assertEquals ( 8, $curpos );
		
		//whence: 2 = SEEK_END, the end of file
		fseek ( $fh, - 5, SEEK_END );
		$curpos = ftell ( $fh );
		$this->assertEquals ( strlen ( $fileContent ) - 5, $curpos );
		
		fclose ( $fh );
	}
	
	//Test stat. return an array. 
	public function testStat() {
		$fileContent = "test stat file";
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/teststat.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		file_put_contents ( $fileName, $fileContent );
		
		$fh = fopen ( $fileName, 'r' );
		$array = fstat ( $fh );
		
		$this->assertNotNull ( $array );
		$this->assertTrue ( $array ["atime"] > 0 );
		$this->assertTrue ( $array ["atime"] <= time () );
		$this->assertEquals ( strlen ( $fileContent ), $array ["size"] );
		
		fclose ( $fh );
	
	}
	
	/**
	 * Flush seems not work here.
	 *
	 */
	public function testFlush() {
		$fileContent = "test flush file";
		$containerName = $this->getContainerName ();
		$fileName = 'azure://' . $containerName . '/testflush.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		file_put_contents ( $fileName, $fileContent );
		
		$newContent = "this is just a test";
		$fh = fopen ( $fileName, 'r+' );
		fwrite ( $fh, $newContent );
		$result = fflush ( $fh );
		
		$this->assertTrue ( $result );
		$this->assertEquals ( file_get_contents ( $fileName ), $fileContent . $newContent );
		
		fclose ( $fh );
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
		$storageClient->getBlob ( $containerName, 'testread.txt', $localFile );
		$this->assertEquals ( implode ( "", $content ), file_get_contents ( $localFile ) );
		
		unlink ( $localFile );
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
		
		$this->assertTrue ( $thrownException, "Maybe prompt user 'Not allow path like a/b/c ...'" ); // Maybe prompt user "Not allow path like a/b/c ..."
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
			$this->_assertExceptionMessageContain ( $e, "The specified container does not exist." );
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
	
	//Test read directory
	public function testReadDirectory() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$containerName = $this->getContainerName ();
		
		$content = "I love hello kitty.";
		$blobName = "blobfile";
		
		//prepare resources
		mkdir ( 'azure://' . $containerName );
		for($i = 0; $i < 10; $i ++) {
			file_put_contents ( 'azure://' . $containerName . "/" . $blobName . $i, $content );
		}
		
		$dh = opendir ( 'azure://' . $containerName );
		
		//return the filename items opened by opendir()
		$i = 0;
		while ( ($file_dir = readdir ( $dh )) !== false ) {
			//the filenames are returned in the order in which they are stored by the filesystem.
			$this->assertEquals ( $blobName . $i ++, $file_dir );
		}
		closedir ( $dh );
	}
	
	//Test rewind directory
	public function testRewindDirectory() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$containerName = $this->getContainerName ();
		
		$content = "I love hello kitty.";
		$blobName = "blobfile";
		
		//prepare resources
		mkdir ( 'azure://' . $containerName );
		for($i = 0; $i < 10; $i ++) {
			file_put_contents ( 'azure://' . $containerName . "/" . $blobName . $i, $content );
		}
		
		$dh = opendir ( 'azure://' . $containerName );
		
		while ( ($file_dir = readdir ( $dh )) !== false ) {
		}
		$this->assertFalse ( readdir ( $dh ) );
		
		//reset the directory
		rewind ( $dh );
		$this->assertEquals ( readdir ( $dh ), $blobName . 0 );
		
		closedir ( $dh );
	}
	
	//Test close directory
	//Close directory not effect file read operation.(You can also get blob from container.)
	public function testCloseDirectory() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$containerName = $this->getContainerName ();
		
		$content = "I love hello kitty.";
		$blobName = "blobfile";
		
		//prepare resources
		mkdir ( 'azure://' . $containerName );
		file_put_contents ( 'azure://' . $containerName . "/" . $blobName, $content );
		
		$dh = opendir ( 'azure://' . $containerName );
		
		$result = file_get_contents ( 'azure://' . $containerName . "/" . $blobName );
		$this->assertEquals ( $result, $content );
		closedir ( $dh );
		$exception = null;
		try {
			$result = file_get_contents ( 'azure://' . $containerName . "/" . $blobName );
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->assertNull ( $exception );
	}
	
	public function testRenameBlob() {
		$containerName = $this->getContainerName ();
		$sourceFileName = 'azure://' . $containerName . '/test.txt';
		$destinationFileName = 'azure://' . $containerName . '/test2.txt';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		$fh = fopen ( $sourceFileName, 'w' );
		fwrite ( $fh, "Hello world!" );
		fclose ( $fh );
		
		rename ( $sourceFileName, $destinationFileName );
		
		$storageClient->unregisterStreamWrapper ();
		
		$instance = $storageClient->getBlobInstance ( $containerName, 'test2.txt' );
		$this->assertEquals ( 'test2.txt', $instance->Name );
	}
	
	//Not support rename container.
	public function testRenameContainer() {
		$containerName1 = $this->getContainerName ();
		$containerName2 = $this->getContainerName ();
		
		$sourceFileName = 'azure://' . $containerName1;
		$destinationFileName = 'azure://' . $containerName2;
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->registerStreamWrapper ();
		
		mkdir ( 'azure://' . $containerName1 );
		try {
			rename ( $sourceFileName, $destinationFileName );
			$storageClient->unregisterStreamWrapper ();
			
			$instance = $storageClient->getContainer ( $containerName2 );
			$this->assertEquals ( $containerName2, $instance->Name );
		} catch ( Exception $ex ) {
			$this->fail ( "Rename on container is not supportted." );
		}
	
	}
}

// Call Microsoft_Azure_BlobStreamTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_BlobStreamTest::main") {
	Microsoft_Azure_BlobStreamTest::main ();
}

?>