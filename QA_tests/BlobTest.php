<?php
if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_BlobTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'Microsoft/Azure/Storage/Blob.php';

class Microsoft_Azure_BlobTest extends PHPUnit_Framework_TestCase {
	static $path;
	
	protected static $uniqId = 0;
	
	protected $_tempFiles = array ();
	
	protected $_tempContainers = array ();
	
	public function __construct() {
		require_once 'TestConfiguration.php';
		self::$path = dirname ( __FILE__ ) . '/datas/';
	}
	
	protected function setUp() {
	
	}
	
	protected function tearDown() {
		if (count ( $this->_tempFiles ) > 0) {
			foreach ( $this->_tempFiles as $file )
				try {
					unlink ( $file );
				} catch ( Exception $e ) {
					// ignore
				}
			
			$this->_tempFiles = array ();
		}
		
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
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_BlobTest" );
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
		array_push ( $this->_tempFiles, $fileName );
		return $fileName;
	}
	
	private function _createLargeBlobFile($filename, $size = Microsoft_Azure_Storage_Blob::MAX_BLOB_SIZE) {
		$fh = fopen ( $filename, 'w' );
		$stringData = "Hello Ketty. I love this cat.Hello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this catHello Ketty. I love this catHello Ketty. I love this catHello Ketty. \n
		I love this cat\n";
		$ls = strlen ( $stringData );
		$large_size = $size / $ls; //File size should be large than this.
		for($i = 0; $i < $large_size + 1; $i ++) {
			fwrite ( $fh, $stringData );
		}
		fclose ( $fh );
	}
	
	//Container names must be from 3 through 63 characters long. 
	public function testNamingLength_createContainer_1() {
		//Test when container name length is less than 3.
		$containerName = "aa";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "One of the request inputs is out of range.", $ex->getMessage () );
		}
	}
	
	//Container names must be from 3 through 63 characters long. 
	public function testNamingLength_createContainer_2() {
		//Test when container name length is 3.
		$containerName = "aaa";
		$storageClient = $this->_createStorageClient ();
		$result = $storageClient->createContainer ( $containerName );
		$this->assertEquals ( $containerName, $result->Name );
		
		$storageClient->deleteContainer ( $containerName );
	}
	
	//Container names must be from 3 through 63 characters long. 
	public function testNamingLength_createContainer_3() {
		//Test when container name length is 63.
		$containerName = str_repeat ( "a", 63 ); // "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
		$storageClient = $this->_createStorageClient ();
		$result = $storageClient->createContainer ( $containerName );
		$this->assertEquals ( $containerName, $result->Name );
		$storageClient->deleteContainer ( $containerName );
	}
	
	//Container names must be from 3 through 63 characters long. 
	public function testNamingLength_createContainer_4() {
		//Test when container name length is more than 63.
		$containerName = str_repeat ( "a", 64 ); //"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "One of the request inputs is out of range.", $ex->getMessage () );
		}
	}
	
	//Container names must start with a letter or number
	public function testContainerPrefix_createContainer_1() {
		//Test when container name is not start with a letter or a number.
		$containerName = "#container";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "One of the request inputs is out of range.", $ex->getMessage () );
		}
	
	}
	
	//Container names must start with a letter or number
	public function testContainerPrefix_createContainer_2() {
		//Test when container name is start with a letter or a number.
		$containerName1 = "cha-container";
		$containerName2 = "22-container";
		
		$storageClient = $this->_createStorageClient ();
		$result1 = $storageClient->createContainer ( $containerName1 );
		$result2 = $storageClient->createContainer ( $containerName2 );
		
		$this->assertEquals ( $containerName1, $result1->Name );
		$this->assertEquals ( $containerName2, $result2->Name );
		
		$storageClient->deleteContainer ( $containerName1 );
		$storageClient->deleteContainer ( $containerName2 );
	
	}
	
	//Container names can contain only letters, numbers, and the dash (-) character. 
	public function testCharacterRule_createContainer_1() {
		//Test when container name contain only letters, numbers and the dash(-) character.
		$containerName = "test-1-container";
		$storageClient = $this->_createStorageClient ();
		$result = $storageClient->createContainer ( $containerName );
		$this->assertEquals ( $containerName, $result->Name );
		$storageClient->deleteContainer ( $containerName );
	}
	
	//Container names can contain only letters, numbers, and the dash (-) character.
	public function testCharacterRule_createContainer_2() {
		//Test when container name contain with '#'.
		$containerName = "test#-1-container";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "One of the request inputs is out of range.", $ex->getMessage () );
		}
	}
	
	//Container names can contain only letters, numbers, and the dash (-) character.
	public function testCharacterRule_createContainer_3() {
		//Test when container name contain " ".
		$containerName = "test-1-con tainer";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "One of the request inputs is out of range.", $ex->getMessage () );
		}
	}
	
	//Test when container name is null.
	public function testCharacterRule_createContainer_4() {
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ();
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "Container name is not specified.", $ex->getMessage () );
		}
	}
	
	//Every dash (-) character must be immediately preceded and followed by a letter or number.
	public function testDash_createContainer() {
		//Test when container name contain '--'.
		$containerName = "test-1--container";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "One of the request inputs is out of range.", $ex->getMessage () );
		}
	}
	
	//The container name can not repeat.
	public function testRepeat_createContainer() {
		$containerName = "repeat-container";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "The specified container already exists.", $ex->getMessage () );
			$storageClient->deleteContainer ( $containerName );
		}
	}
	
	// All letters in a container name must be lowercase.
	public function testLower_createContainer() {
		//Test when container name container uppercase.
		$containerName = "CONTAINER";
		$storageClient = $this->_createStorageClient ();
		try {
			$result = $storageClient->createContainer ( $containerName );
			$this->fail ( "fail -> The container name is illegal." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "One of the request inputs is out of range.", $ex->getMessage () );
		}
	}
	
	//Test set and get container acl with False
	public function testSetAndGetContainerAcl_False() {
		$containerName = "getacl-test-container1";
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->setContainerAcl ( $containerName, FALSE );
		
		$this->assertEquals ( FALSE, $storageClient->getContainerAcl ( $containerName ) );
		$storageClient->deleteContainer ( $containerName );
	}
	
	//Test set and get container acl with True
	public function testSetAndGetContainerAcl_True() {
		$containerName = "getacl-test-container2";
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->setContainerAcl ( $containerName, TRUE );
		
		$this->assertEquals ( TRUE, $storageClient->getContainerAcl ( $containerName ) );
		$storageClient->deleteContainer ( $containerName );
	}
	
	//Test delete containers.
	public function testDeleteContainer() {
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( "delete-container1" );
		$storageClient->createContainer ( "delete-container2" );
		$this->assertEquals ( 2, count ( $storageClient->listContainers ( "delete" ) ) );
		
		$storageClient->deleteContainer ( "delete-container1" );
		$storageClient->deleteContainer ( "delete-container2" );
		$this->assertEquals ( 0, count ( $storageClient->listContainers ( "delete" ) ) );
	}
	
	/**
	 * Test set container metadata
	 */
	public function testSetContainerMetadata_1() {
		$storageClient = $this->_createStorageClient ();
		try {
			//Test when container name is not specified.
			$storageClient->setContainerMetadata ( "", "" );
			$this->fail ( "fail -> The container name is not specified." );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "Container name is not specified.", $ex->getMessage () );
		}
	}
	
	// The container metadata can set as one or more user-defined name/value pairs. "" set as array().
	public function testSetContainerMetadata_2() {
		$containerName = "metadata-test-container1";
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->setContainerMetadata ( $containerName, "" );
		
		$this->assertEquals ( array (), $storageClient->getContainerMetadata ( $containerName ) );
		$storageClient->deleteContainer ( $containerName );
	}
	
	//The container metadata can set as one user-defined name/value pairs.
	public function testSetContainerMetadata_3() {
		$containerName = "metadata-test-container2";
		$metadata = array ("mode" => "test" );
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		$storageClient->setContainerMetadata ( $containerName, $metadata );
		$this->assertEquals ( $metadata, $storageClient->getContainerMetadata ( $containerName ) );
		// set metadata again
		$storageClient->setContainerMetadata ( $containerName, $metadata );
		$this->assertEquals ( $metadata, $storageClient->getContainerMetadata ( $containerName ) );
		
		$storageClient->deleteContainer ( $containerName );
	}
	
	public function testGetContainer() {
		$containerName = "get-metadata-container";
		$metadata = array ("azure" => "blob", "port" => 10000 );
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName, $metadata );
		
		$result = $storageClient->getContainerMetadata ( $containerName );
		$this->assertEquals ( $result, $metadata );
		
		$result = $storageClient->getContainer ( $containerName );
		$this->assertEquals ( $containerName, $result->name );
		$this->assertEquals ( $metadata, $result->metadata );
		
		$storageClient->deleteContainer ( $containerName );
	}
	
	/**
	 * Test list containers
	 */
	public function testListContainers() {
		$storageClient = $this->_createStorageClient ();
		
		$containers = array ("listcontainer-test1", "listcontainer-test2", "listcontainer-test3" );
		foreach ( $containers as $container )
			$storageClient->createContainer ( $container );
		
		$result = $storageClient->listContainers ();
		
		$names = array ();
		foreach ( $result as $container )
			array_push ( $names, $container->name );
			
		// container name must appear in list container result	
		foreach ( $containers as $container )
			$this->assertTrue ( in_array ( $container, $names ) );
		
		foreach ( $containers as $container )
			$storageClient->deleteContainer ( $container );
	}
	
	//Prefix: Filters the results to return only containers whose name begins with the specified prefix.
	public function testListContainersWithPrefix() {
		$storageClient = $this->_createStorageClient ();
		
		$containers = array ("1-listcontainer-test", "11-listcontainer-test", "2-listcontainer-test" );
		foreach ( $containers as $container )
			$storageClient->createContainer ( $container );
		
		$result = $storageClient->listContainers ( "1" );
		$this->assertEquals ( 2, count ( $result ) );
		
		foreach ( $containers as $container )
			$storageClient->deleteContainer ( $container );
	}
	
	//Maxresults: Specifies the maximum number of containers to return.
	public function testListContainersWithMaxresult() {
		$storageClient = $this->_createStorageClient ();
		$containers = array ("listcontainer-maxresult-1", "listcontainer-maxresult-2", "listcontainer-maxresult-3" );
		foreach ( $containers as $container )
			$storageClient->createContainer ( $container );
		
		$result = $storageClient->listContainers ( "listcontainer-maxresult", 3 );
		$this->assertEquals ( 3, count ( $result ) );
		
		foreach ( $containers as $container )
			$storageClient->deleteContainer ( $container );
	}
	
	/**
	 * Test get blob
	 */
	public function testGetBlob_1() {
		$containerName = "getblob-test-container1";
		$blobName = 'images/WindowsAzure.gif';
		$storageClient = $this->_createStorageClient ( $containerName );
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif' );
		
		$fileName = tempnam ( '', 'tst' );
		$storageClient->getBlob ( $containerName, $blobName, $fileName );
		
		$this->assertTrue ( file_exists ( $fileName ) );
		$this->assertEquals ( file_get_contents ( self::$path . 'WindowsAzure.gif' ), file_get_contents ( $fileName ) );
		
		// Remove file
		unlink ( $fileName );
		$storageClient->deleteContainer ( $containerName );
	}
	
	/**
	 * Container name can not be null.
	 */
	public function testGetBlob_2() {
		$storageClient = $this->_createStorageClient ();
		
		try {
			$storageClient->getBlob ( "" );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "Container name is not specified.", $ex->getMessage () );
		}
	}
	
	//Blob name can not be null.
	public function testGetBlob_3() {
		$containerName = "getblob-test-container3";
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif' );
		try {
			$storageClient->getBlob ( $containerName, "" );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "Blob name is not specified.", $ex->getMessage () );
		}
		$storageClient->deleteContainer ( $containerName );
	}
	
	//File path can not be null.
	public function testGetBlob_4() {
		$containerName = "getblob-test-container4";
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif' );
		try {
			$storageClient->getBlob ( $containerName, 'images/WindowsAzure.gif' );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "Local file name is not specified.", $ex->getMessage () );
		}
		$storageClient->deleteContainer ( $containerName );
	}
	
	//This operation sets user-defined metadata for the specified blob as one or more name-value pairs.
	public function testSetBlobMetadata_5() {
		$containerName = "setblob-test-container5";
		$blobName = 'images/WindowsAzure.gif';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif' );
		
		$storageClient->setBlobMetadata ( $containerName, $blobName, array ("mode" => "dev" ) );
		$storageClient->setBlobMetadata ( $containerName, $blobName, array ("mode" => "test" ) );
		$metadata = $storageClient->getBlobMetadata ( $containerName, $blobName );
		
		$this->assertEquals ( 'test', $metadata ['mode'] );
		$storageClient->deleteContainer ( $containerName );
	}
	
	//This operation sets user-defined metadata for the specified blob as one or more name-value pairs. Metadata value can not be a string.
	public function testSetBlobMetadata_6() {
		$containerName = "setblob-test-container6";
		$blobName = 'images/WindowsAzure.gif';
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif' );
		try {
			$storageClient->setBlobMetadata ( $containerName, $blobName, "" );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "Metadata value can not be a string.", $ex->getMessage () );
		}
		$storageClient->deleteContainer ( $containerName );
	}
	
	//Test getBlobMetadata.
	public function testGetBlobMetadata() {
		$containerName = "getblob-metadata-container";
		$blobName = 'images/WindowsAzure.gif';
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif', array ("count" => 1, "lang" => "php" ) );
		$metadata = $storageClient->getBlobMetadata ( $containerName, $blobName );
		
		$this->assertEquals ( 1, $metadata ["count"] );
		$this->assertEquals ( "php", $metadata ["lang"] );
		
		$storageClient->deleteContainer ( $containerName );
	}
	
	/**
	 * Create container->upload blob->get Blob 
	 *
	 */
	public function testUploadSimpleBlob() {
		$storageClient = $this->_createStorageClient ();
		
		$containerName = "uploadblob-test";
		$blob_name = "simpletestblob";
		//Test upload
		try {
			$storageClient->createContainer ( $containerName );
			//Test upload a file as a blob
			$storageClient->putBlob ( $containerName, $blob_name, self::$path . "simple_blob.txt" );
			$blob = $storageClient->getBlobInstance ( $containerName, $blob_name );
			$this->assertEquals ( $containerName, $blob->Container );
			$this->assertEquals ( $blob_name, $blob->Name );
			$this->assertEquals ( filesize ( self::$path . "simple_blob.txt" ), $blob->Size );
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
		$storageClient->deleteContainer ( $containerName );
	}
	
	public function testGetSimpleBlob() {
		$storageClient = $this->_createStorageClient ();
		
		$containerName = "getblob-test";
		$blob_name = "simpletestblob";
		//Test upload
		try {
			$storageClient->createContainer ( $containerName );
			//Test upload a file as a blob
			$blobfile = self::$path . "blob_test.tmp";
			$storageClient->putBlob ( $containerName, $blob_name, self::$path . "simple_blob.txt" );
			$storageClient->getBlob ( $containerName, $blob_name, $blobfile );
			$this->assertEquals ( file_get_contents ( self::$path . "simple_blob.txt" ), file_get_contents ( $blobfile ) );
			unlink ( $blobfile );
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
		$storageClient->deleteContainer ( $containerName );
	}
	
	public function testUploadLargeSize() {
		$storageClient = $this->_createStorageClient ();
		
		$containerName = "upload-largeblob-test-container";
		$blob_name = "largeblob";
		$file = self::$path . "large_size_blob.txt";
		//Test upload
		try {
			$storageClient->createContainer ( $containerName );
			array_push ( $this->_tempContainers, $containerName );
			if (file_exists ( $file ) && filesize ( $file ) < Microsoft_Azure_Storage_Blob::MAX_BLOB_SIZE) {
				unlink ( $file );
			}
			
			if (! file_exists ( $file )) {
				$this->_createLargeBlobFile ( $file );
			}
			
			//Test upload a file as a blob
			$blobfile = self::$path . "blob_test_large.tmp";
			$storageClient->putBlob ( $containerName, $blob_name, $file );
			$storageClient->getBlob ( $containerName, $blob_name, $blobfile );
			array_push ( $this->_tempFiles, $file, $blobfile );
			$this->assertEquals ( file_get_contents ( $file ), file_get_contents ( $blobfile ) );
		
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
	}
	
	public function testUploadLargeSizeHalfOfMaxSize() {
		$storageClient = $this->_createStorageClient ();
		
		$containerName = "upload-largeblob-test-container";
		$blob_name = "largeblob";
		$file = self::$path . "large_size_blob.txt";
		//Test upload
		try {
			$storageClient->createContainer ( $containerName );
			array_push ( $this->_tempContainers, $containerName );
			if (file_exists ( $file ) && filesize ( $file ) < Microsoft_Azure_Storage_Blob::MAX_BLOB_SIZE) {
				unlink ( $file );
			}
			
			if (! file_exists ( $file )) {
				$this->_createLargeBlobFile ( $file, Microsoft_Azure_Storage_Blob::MAX_BLOB_SIZE / 2 );
			}
			
			//Test upload a file as a blob
			$blobfile = self::$path . "blob_test_large.tmp";
			$storageClient->putBlob ( $containerName, $blob_name, $file );
			$storageClient->getBlob ( $containerName, $blob_name, $blobfile );
			array_push ( $this->_tempFiles, $file, $blobfile );
			$this->assertEquals ( file_get_contents ( $file ), file_get_contents ( $blobfile ) );
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
	}
	
	/**
	 * Test list blobs
	 */
	public function testListBlobs() {
		$containerName = "listingblob";
		$storageClient = $this->_createStorageClient ();
		
		// Create a file
		$fileName = $this->_createTempFile ( "test list blobs" );
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'blob1.txt', $fileName );
		$storageClient->putBlob ( $containerName, 'blob2.txt', $fileName );
		$storageClient->putBlob ( $containerName, 'blob3.txt', $fileName );
		
		$result = $storageClient->listBlobs ( $containerName );
		$this->assertEquals ( 3, count ( $result ) );
		
		// limit result, dead loop, could have bug
		// $result = $storageClient->listBlobs($containerName,'',1);       		
		

		$storageClient->deleteContainer ( $containerName );
	}
	
	/**
	 * Test update blob content and metadata
	 */
	public function testUpdateBlob() {
		$containerName = "updateblob";
		$blobName = "blob1.txt";
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif' );
		
		$fileName = $this->_createTempFile ( "update blobs content" );
		$blob = $storageClient->putBlob ( $containerName, $blobName, $fileName, array ('update' => true ) );
		$this->assertEquals ( 'blob1.txt', $blob->name );
		$this->assertEquals ( $containerName, $blob->container );
		
		$metadata = $storageClient->getBlobMetadata ( $containerName, $blobName );
		$this->assertEquals ( true, $metadata ['update'] );
		
		$fileName = tempnam ( '', 'tst' );
		$storageClient->getBlob ( $containerName, $blobName, $fileName );
		
		$this->assertEquals ( "update blobs content", file_get_contents ( $fileName ) );
		
		unlink ( $fileName );
		$storageClient->deleteContainer ( $containerName );
	}
	
	/**
	 * Test delete blob
	 */
	public function testDeleteBlob() {
		$containerName = "deleteblob";
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		
		$fileName = $this->_createTempFile ( "update blobs content" );
		$storageClient->putBlob ( $containerName, 'test.txt', $fileName );
		
		$result = $storageClient->listBlobs ( $containerName );
		$this->assertEquals ( 1, count ( $result ) );
		
		$storageClient->deleteBlob ( $containerName, 'test.txt' );
		
		$result = $storageClient->listBlobs ( $containerName );
		$this->assertEquals ( 0, count ( $result ) );
		
		$storageClient->deleteContainer ( $containerName );
	}
	
	/**
	 * Test different time zone
	 *
	 */
	public function testTimezone() {
		$containerName = "timezonecontainer";
		$timezones = array ('PRC', 'EST', 'UTC', 'CET', 'GMT', 'Etc/GMT+0', 'Etc/GMT+1', 'Etc/GMT+10', 'Etc/GMT+11', 'Etc/GMT+12', 'Etc/GMT+2', 'Etc/GMT+3', 'Etc/GMT+4', 'Etc/GMT+5', 'Etc/GMT+6', 'Etc/GMT+7', 'Etc/GMT+8', 'Etc/GMT+9', 'Etc/GMT-0', 'Etc/GMT-1', 'Etc/GMT-10', 'Etc/GMT-11', 'Etc/GMT-12', 'Etc/GMT-13', 'Etc/GMT-14', 'Etc/GMT-2', 'Etc/GMT-3', 'Etc/GMT-4', 'Etc/GMT-5', 'Etc/GMT-6', 'Etc/GMT-7', 'Etc/GMT-8', 'Etc/GMT-9' );
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		array_push ( $this->_tempContainers, $containerName );
		try {
			foreach ( $timezones as $timezone ) {
				date_default_timezone_set ( $timezone );
				$storageClient->listBlobs ( $containerName );
			}
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
	
	}
	
	/**
	 * Test copy blob in single container
	 */
	public function testCopyBlob_1() {
		if (REMOTE_STORAGE_TEST) {
			
			$storageClient = $this->_createStorageClient ();
			$container1 = $this->getContainerName ();
			
			$storageClient->createContainer ( $container1 );
			
			$metadata = array ("mode" => "test", "count" => 1 );
			$storageClient->putBlob ( $container1, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif', $metadata );
			
			$destination = $storageClient->copyBlob ( $container1, 'images/WindowsAzure.gif', $container1, 'images/WindowsAzureCopy.gif' );
			
			$this->assertEquals ( 'images/WindowsAzureCopy.gif', $destination->Name );
			$this->assertEquals ( $container1, $destination->Container );
			
			// check metadata
			$result = $storageClient->getBlobMetadata ( $container1, 'images/WindowsAzureCopy.gif' );
			$this->assertEquals ( $metadata, $result );
			
			// check file content
			$blobFile = tempnam ( '', 'tst' );
			$storageClient->getBlob ( $container1, 'images/WindowsAzureCopy.gif', $blobFile );
			$this->assertEquals ( file_get_contents ( $blobFile ), file_get_contents ( self::$path . 'WindowsAzure.gif' ) );
			
			unlink ( $blobFile );
		}
	}
	
	/**
	 * Test copy blob in different container
	 */
	public function testCopyBlob_2() {
		if (REMOTE_STORAGE_TEST) {
			$storageClient = $this->_createStorageClient ();
			$container1 = $this->getContainerName ();
			$container2 = $this->getContainerName ();
			
			$storageClient->createContainer ( $container1 );
			$storageClient->createContainer ( $container2 );
			
			$metadata = array ("mode" => "test", "count" => 1 );
			$storageClient->putBlob ( $container1, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif', $metadata );
			
			$metadata = array ("mode" => "dev", "count" => 2 );
			// copy to another container and update metadata
			$destination = $storageClient->copyBlob ( $container1, 'images/WindowsAzure.gif', $container2, 'images/WindowsAzureCopy.gif', $metadata );
			
			$this->assertEquals ( 'images/WindowsAzureCopy.gif', $destination->Name );
			$this->assertEquals ( $container2, $destination->Container );
			
			// check metadata
			$result = $storageClient->getBlobMetadata ( $container2, 'images/WindowsAzureCopy.gif' );
			$this->assertEquals ( $metadata, $result );
			
			// check file content
			$blobFile = tempnam ( '', 'tst' );
			$storageClient->getBlob ( $container2, 'images/WindowsAzureCopy.gif', $blobFile );
			$this->assertEquals ( file_get_contents ( $blobFile ), file_get_contents ( self::$path . 'WindowsAzure.gif' ) );
			
			unlink ( $blobFile );
		}
	}
	
	/**
	 * The data should be parsed at the same time when it's being downloaded from azure server.
	 *
	 */
	public function testParseDataWhenDownload() {
		$this->fail ( "API not support" );
	}
}

// Call Microsoft_Azure_BlobStorageTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_BlobTest::main") {
	Microsoft_Azure_BlobTest::main ();
}
	
