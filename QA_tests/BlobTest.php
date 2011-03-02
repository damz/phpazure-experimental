<?php
if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_BlobTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'Microsoft/WindowsAzure/Storage/Blob.php';

class Microsoft_WindowsAzure_BlobTest extends PHPUnit_Framework_TestCase {
	static $path;
	
	protected static $uniqId = 0;
	
	protected $_tempFiles = array ();
	
	protected $_tempContainers = array ();
	
	public function __construct() {
		require_once 'TestConfiguration.php';
		self::$path = dirname ( __FILE__ ) . '/datas/';
		self::$uniqId = mt_rand ( 0, 10000 );
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
	
	protected function _deteleContainerAfterReturn($name) {
		if (is_array ( $name ))
			$this->_tempContainers = array_merge ( $this->_tempContainers, $name );
		else
			$this->_tempContainers [] = $name;
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_WindowsAzure_BlobTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function _createStorageClient() {
		return new Microsoft_WindowsAzure_Storage_Blob ( BLOB_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_WindowsAzure_RetryPolicy::retryN ( 10, 250 ) );
	}
	
	protected function _createTempFile($content) {
		$fileName = tempnam ( '', 'tst' );
		$fp = fopen ( $fileName, 'w' );
		fwrite ( $fp, $content );
		fclose ( $fp );
		array_push ( $this->_tempFiles, $fileName );
		return $fileName;
	}
	
	protected function _createLargeBlobFile($filename) {
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
		$large_size = Microsoft_WindowsAzure_Storage_Blob::MAX_BLOB_SIZE / $ls + 20; //File size should be large than this.
		for($i = 0; $i < $large_size; $i ++) {
			fwrite ( $fh, $stringData );
		}
		fclose ( $fh );
	}
	
	/**
	 * Test container exists
	 */
	public function testContainerExists() {
		$storageClient = $this->_createStorageClient ();
		$containerName = $this->getContainerName ();
		$storageClient->createContainer ( $containerName );
		$result = $storageClient->containerExists ( $containerName );
		$this->assertTrue ( $result );
		
		// test containerExists after delete
		$storageClient->deleteContainer ( $containerName );
		$result = $storageClient->containerExists ( $containerName );
		$this->assertFalse ( $result );
		
		// test no exist container
		$containerName = "not-exists-container";
		$result = $storageClient->containerExists ( $containerName );
		$this->assertFalse ( $result );
	}
	
	/**
	 * Test blob exists
	 */
	public function testBlobExists() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		
		$fileName = $this->_createTempFile ( "test blob exists" );
		
		$blobName = 'test.txt';
		$storageClient->putBlob ( $containerName, $blobName, $fileName );
		
		$result = $storageClient->blobExists ( $containerName, $blobName );
		$this->assertTrue ( $result );
		
		// test after delete blob
		$storageClient->deleteBlob ( $containerName, $blobName );
		$result = $storageClient->blobExists ( $containerName, $blobName );
		$this->assertFalse ( $result );
		
		$result = $storageClient->blobExists ( $containerName, "nosuchblob.txt" );
		$this->assertFalse ( $result );
	
	}
	//Container names must be from 3 through 63 characters long. 
	public function testNamingLength_createContainer_1() {
		//Test when container name length is less than 3.
		$containerName = "aa";
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.", $exception->getMessage () );
	
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
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.", $exception->getMessage () );
	}
	
	//Container names must start with a letter or number
	public function testContainerPrefix_createContainer_1() {
		//Test when container name is not start with a letter or a number.
		$containerName = "#container";
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.", $exception->getMessage () );
	
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
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.", $exception->getMessage () );
	}
	
	//Container names can contain only letters, numbers, and the dash (-) character.
	public function testCharacterRule_createContainer_3() {
		//Test when container name contain " ".
		$containerName = "test-1-con tainer";
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.", $exception->getMessage () );
	}
	
	//Test when container name is null.
	public function testCharacterRule_createContainer_4() {
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			$storageClient->createContainer ();
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name is not specified.", $exception->getMessage () );
	}
	
	//Every dash (-) character must be immediately preceded and followed by a letter or number.
	public function testDash_createContainer() {
		//Test when container name contain '--'.
		$containerName = "test-1--container";
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.", $exception->getMessage () );
	}
	
	//The container name can not repeat.
	public function testRepeat_createContainer() {
		$containerName = "repeat-container";
		$storageClient = $this->_createStorageClient ();
		$this->_deteleContainerAfterReturn ( $containerName );
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		
		$this->assertNotNull ( $exception, "fail -> Cannot create container twice." );
		$this->assertEquals ( "The specified container already exists.", $exception->getMessage () );
	}
	
	// All letters in a container name must be lowercase.
	public function testLower_createContainer() {
		//Test when container name container uppercase.
		$containerName = "CONTAINER";
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			$storageClient->createContainer ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is illegal." );
		$this->assertEquals ( "Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.", $exception->getMessage () );
	}
	
	//Test set and get container acl with False
	public function testSetAndGetContainerAcl_False() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->setContainerAcl ( $containerName, FALSE );
		
		$this->assertEquals ( FALSE, $storageClient->getContainerAcl ( $containerName ) );
	}
	
	//Test set and get container acl with True
	public function testSetAndGetContainerAcl_True() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->setContainerAcl ( $containerName, TRUE );
		
		$this->assertEquals ( TRUE, $storageClient->getContainerAcl ( $containerName ) );
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
		$exception = null;
		try {
			//Test when container name is not specified.
			$storageClient->setContainerMetadata ( "", "" );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> The container name is not specified." );
		$this->assertEquals ( "Container name is not specified.", $exception->getMessage () );
	}
	
	// The container metadata can set as one or more user-defined name/value pairs. "" is invalid.
	public function testSetContainerMetadata_2() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		
		$exception = null;
		try {
			$storageClient->setContainerMetadata ( $containerName, "" );
			$this->fail ( "null is invalid value for metadata." );
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->assertEquals ( "Meta data should be an array of key and value pairs.", $exception->getMessage () );
	
	}
	
	//The container metadata can set as one user-defined name/value pairs.
	public function testSetContainerMetadata_3() {
		$containerName = $this->getContainerName ();
		$metadata = array ("mode" => "test" );
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		$storageClient->setContainerMetadata ( $containerName, $metadata );
		$this->assertEquals ( $metadata, $storageClient->getContainerMetadata ( $containerName ) );
		// set metadata again
		$storageClient->setContainerMetadata ( $containerName, $metadata );
		$this->assertEquals ( $metadata, $storageClient->getContainerMetadata ( $containerName ) );
	
	}
	
	public function testGetContainer() {
		$containerName = $this->getContainerName ();
		$metadata = array ("azure" => "blob", "port" => 10000 );
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName, $metadata );
		
		$result = $storageClient->getContainerMetadata ( $containerName );
		$this->assertEquals ( $result, $metadata );
		
		$result = $storageClient->getContainer ( $containerName );
		$this->assertEquals ( $containerName, $result->name );
		$this->assertEquals ( $metadata, $result->metadata );
	}
	
	/**
	 * Test list containers
	 */
	public function testListContainers() {
		$storageClient = $this->_createStorageClient ();
		
		$containers = array ("listcontainer-test1", "listcontainer-test2", "listcontainer-test3" );
		$this->_deteleContainerAfterReturn ( $containers );
		
		foreach ( $containers as $container )
			$storageClient->createContainer ( $container );
		
		$result = $storageClient->listContainers ();
		
		$names = array ();
		foreach ( $result as $container )
			array_push ( $names, $container->name );
			
		// container name must appear in list container result	
		foreach ( $containers as $container )
			$this->assertTrue ( in_array ( $container, $names ) );
	}
	
	//Prefix: Filters the results to return only containers whose name begins with the specified prefix.
	public function testListContainersWithPrefix() {
		$storageClient = $this->_createStorageClient ();
		
		$containers = array ("1-listcontainer-test", "11-listcontainer-test", "2-listcontainer-test" );
		$this->_deteleContainerAfterReturn ( $containers );
		
		foreach ( $containers as $container )
			$storageClient->createContainer ( $container );
		
		$result = $storageClient->listContainers ( "1" );
		$this->assertEquals ( 2, count ( $result ) );
	}
	
	//Maxresults: Specifies the maximum number of containers to return.
	public function testListContainersWithMaxresult() {
		$storageClient = $this->_createStorageClient ();
		$containers = array ("listcontainer-maxresult-1", "listcontainer-maxresult-2", "listcontainer-maxresult-3" );
		$this->_deteleContainerAfterReturn ( $containers );
		
		foreach ( $containers as $container )
			$storageClient->createContainer ( $container );
		
		$result = $storageClient->listContainers ( "listcontainer-maxresult", 3 );
		$this->assertEquals ( 3, count ( $result ) );
	}
	
	/**
	 * Test get blob
	 */
	public function testGetBlob_1() {
		$containerName = $this->getContainerName ();
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
	}
	
	/**
	 * Container name can not be null.
	 */
	public function testGetBlob_2() {
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			$storageClient->getBlob ( "" );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		
		$this->assertNotNull ( $exception, "fail -> Missing container name." );
		$this->assertEquals ( "Container name is not specified.", $exception->getMessage () );
	}
	
	//Blob name can not be null.
	public function testGetBlob_3() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif' );
		$exception = null;
		try {
			$storageClient->getBlob ( $containerName, "" );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		
		$this->assertNotNull ( $exception, "fail -> Missing blob name." );
		$this->assertEquals ( "Blob name is not specified.", $exception->getMessage () );
	
	}
	
	//File path can not be null.
	public function testGetBlob_4() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif' );
		
		$exception = null;
		try {
			$storageClient->getBlob ( $containerName, 'images/WindowsAzure.gif' );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> Missing local file name." );
		$this->assertEquals ( "Local file name is not specified.", $exception->getMessage () );
	}
	
	//This operation sets user-defined metadata for the specified blob as one or more name-value pairs.
	public function testSetBlobMetadata_5() {
		$containerName = $this->getContainerName ();
		$blobName = 'images/WindowsAzure.gif';
		
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif' );
		
		$storageClient->setBlobMetadata ( $containerName, $blobName, array ("mode" => "dev" ) );
		$storageClient->setBlobMetadata ( $containerName, $blobName, array ("mode" => "test" ) );
		$metadata = $storageClient->getBlobMetadata ( $containerName, $blobName );
		
		$this->assertEquals ( 'test', $metadata ['mode'] );
	
	}
	
	//This operation sets user-defined metadata for the specified blob as one or more name-value pairs. Metadata value can not be a string.
	public function testSetBlobMetadata_6() {
		$containerName = $this->getContainerName ();
		$blobName = 'images/WindowsAzure.gif';
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif' );
		
		$exception = null;
		try {
			$storageClient->setBlobMetadata ( $containerName, $blobName, "" );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "fail -> Invalid metadata." );
		$this->assertEquals ( "Meta data should be an array of key and value pairs.", $exception->getMessage () );
	}
	
	//Test getBlobMetadata.
	public function testGetBlobMetadata() {
		$containerName = $this->getContainerName ();
		$blobName = 'images/WindowsAzure.gif';
		$storageClient = $this->_createStorageClient ();
		$storageClient->createContainer ( $containerName );
		$storageClient->putBlob ( $containerName, $blobName, self::$path . 'WindowsAzure.gif', array ("count" => 1, "lang" => "php" ) );
		$metadata = $storageClient->getBlobMetadata ( $containerName, $blobName );
		
		$this->assertEquals ( 1, $metadata ["count"] );
		$this->assertEquals ( "php", $metadata ["lang"] );
	
	}
	
	/**
	 * Create container->upload blob->get Blob 
	 *
	 */
	public function testUploadSimpleBlob() {
		$storageClient = $this->_createStorageClient ();
		
		$containerName = $this->getContainerName ();
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
	
	}
	
	public function testGetSimpleBlob() {
		$storageClient = $this->_createStorageClient ();
		
		$containerName = $this->getContainerName ();
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
	}
	
	public function testUploadLargeSizeAndGetBlob() {
		$storageClient = $this->_createStorageClient ();
		
		$containerName = $this->getContainerName ();
		$blob_name = "largeblob";
		$file = self::$path . "large_size_blob.txt";
		//Test upload
		try {
			$storageClient->createContainer ( $containerName );
			array_push ( $this->_tempContainers, $containerName );
			if (file_exists ( $file ) && filesize ( $file ) < Microsoft_WindowsAzure_Storage_Blob::MAX_BLOB_SIZE) {
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
	
	/**
	 * Test list blobs
	 */
	public function testListBlobs() {
		$containerName = $this->getContainerName ();
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
	 * Test list blobs with blob prefix
	 */
	public function testListBlobsWithBlobPrefix() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		// Create a file
		$fileName = $this->_createTempFile ( "test list blobs" );
		$storageClient->createContainer ( $containerName );
		$prefix = "prefix";
		// blob with prefix
		for($i = 1; $i <= 3; $i ++) {
			$storageClient->putBlob ( $containerName, $prefix . 'blob' . $i . '.txt', $fileName );
		}
		
		//blob no prefix
		for($i = 1; $i <= 3; $i ++) {
			$storageClient->putBlob ( $containerName, 'blob' . $i . '.txt', $fileName );
		}
		
		$result = $storageClient->listBlobs ( $containerName, $prefix );
		$this->assertEquals ( 3, count ( $result ) );
	}
	
	/**
	 * Test update blob content and metadata
	 */
	public function testUpdateBlob() {
		$containerName = $this->getContainerName ();
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
		$containerName = $this->getContainerName ();
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
		$containerName = $this->getContainerName ();
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
		
		$storageClient->deleteContainer ( $containerName );
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
			$storageClient->deleteContainer ( $container1 );
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
			
			$storageClient->deleteContainer ( $container1 );
			$storageClient->deleteContainer ( $container2 );
		}
	}
	
	/**
     * Test root container
     */
    public function testRootContainer()
    {
        if (REMOTE_STORAGE_TEST)  
        {
            $containerName = '$root';
           
            $storageClient = $this->_createStorageClient();
            $result = $storageClient->createContainer($containerName);
            $this->assertEquals($containerName, $result->Name);
            
            // containerExists
           $result = $storageClient->containerExists($containerName);
           $this->assertTrue($result);
                       
            // List container
            $result = $storageClient->listContainers();
            $exists = false;
            foreach( $result as $container )
				if ( $containerName == $container->name ){
					$exists = true;
					break;					
				}
			$this->assertTrue($exists);

            
            // ACL
            $storageClient->setContainerAcl($containerName, Microsoft_WindowsAzure_Storage_Blob::ACL_PUBLIC);
            $acl = $storageClient->getContainerAcl($containerName);            
            $this->assertEquals(Microsoft_WindowsAzure_Storage_Blob::ACL_PUBLIC, $acl);
            
            $storageClient->setContainerAcl($containerName, Microsoft_WindowsAzure_Storage_Blob::ACL_PRIVATE);
            $acl = $storageClient->getContainerAcl($containerName);            
            $this->assertEquals(Microsoft_WindowsAzure_Storage_Blob::ACL_PRIVATE, $acl);

            $metadata = array ("count" => 1, "comment" => "php" );
            
            // Metadata
            $storageClient->setContainerMetadata($containerName, $metadata);
            
            $result = $storageClient->getContainerMetadata($containerName);
            $this->assertEquals($metadata, $result);

            // Put blob
            $blobName = 'WindowsAzure.gif';
            $result = $storageClient->putBlob($containerName, $blobName, self::$path . 'WindowsAzure.gif');
   
            $this->assertEquals($containerName, $result->Container);
            $this->assertEquals($blobName, $result->Name);
            
            // blob exists
            $result = $storageClient->blobExists( $containerName, $blobName);
            $this->assertTrue( $result);
            
            // Get blob
            $fileName = tempnam('', 'tst');
            $storageClient->getBlob($containerName, 'WindowsAzure.gif', $fileName);
    
            $this->assertTrue(file_exists($fileName));
            $this->assertEquals(
                file_get_contents(self::$path . 'WindowsAzure.gif'),
                file_get_contents($fileName)
            );
            
            // Remove file
            unlink($fileName);
            
            // Blob metadata
            $storageClient->setBlobMetadata($containerName, $blobName, $metadata);
            
            $result = $storageClient->getBlobMetadata($containerName, 'WindowsAzure.gif');
            $this->assertEquals($metadata, $result);
            
            // List blobs
            $result = $storageClient->listBlobs($containerName);
            
            $exists = false;
            foreach( $result as $blob )
				if ( $blobName == $blob->name ){
					$exists = true;
					break;					
				}
			$this->assertTrue($exists);
            
            // Delete blob
            $storageClient->deleteBlob($containerName, $blobName);
            
            $result = $storageClient->blobExists( $containerName, $blobName);
          	$this->assertFalse( $result);            
          	
          	// delete root container
      	    $storageClient->deleteContainer($containerName);
            $result = $storageClient->containerExists($containerName);
            $this->assertFalse($result);
        }
    }
    
	/**
	 * Test put block(put a simple and small block)
	 */
	public function testPutBlock() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$identifier = "AAA";
		
		try {
			$storageClient->putBlock ( $containerName, $blob_name, $identifier, $blob_block_content );
			$blockList = $storageClient->getBlockList ( $containerName, $blob_name, 0 );
			$this->assertTrue ( sizeof ( $blockList ) > 0 );
			print_r ( $blockList );
			$uncommit_block = $blockList ["UncommittedBlocks"]; // only one uncommit block
			$block = $uncommit_block [0];
			$this->assertEquals ( base64_encode ( $identifier ), $block->Name );
			$this->assertEquals ( strlen ( $blob_block_content ), $block->Size );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Test put block larger than 4M
	 * @see  http://msdn.microsoft.com/en-us/library/dd135726.aspx
	 */
	public function testPutBlockLargerThan4M() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		$MAX_BLOCK = Microsoft_WindowsAzure_Storage_Blob::MAX_BLOB_TRANSFER_SIZE;
		$blob_content = str_repeat ( '.', $MAX_BLOCK + 1 );
		$identifier = "AAA";
		$exceptionThrown = false;
		try {
			$storageClient->putBlock ( $containerName, $blob_name, $identifier, $blob_content );
		} catch ( Exception $e ) {
			//throw new Microsoft_WindowsAzure_Exception('Block size is too big.');
			$exceptionThrown = true;
			$this->assertEquals ( "Block size is too big.", $e->getMessage () );
		}
		$this->assertTrue ( $exceptionThrown );
	}
	
	/**
	 * Test put block and get blocks
	 */
	public function testPutBlockAndGetBlocksByType() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 10;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		for($i = 0; $i < $numberOfParts; $i ++) {
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $blob_block_content );
		}
		
		$blockList = $storageClient->getBlockList ( $containerName, $blob_name, 0 ); // 0 means all type
		

		$all = $this->stat_num ( $blockList );
		$this->assertEquals ( $numberOfParts, $all, "Should have '.$numberOfParts.' blocks" );
	}
	
	/**
	 * Test put blocks to commit blocks. after commit blocks, get blocks by block type seems not work ok.
	 */
	public function testPutAndGetBlocks() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 10;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		//put 10 blocks
		for($i = 0; $i < $numberOfParts; $i ++) {
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $blob_block_content . '__' . $i );
		}
		
		$commit_number = 3;
		$blocks_be_commit = array ();
		for($ci = 0; $ci < $commit_number; $ci ++) {
			$blocks_be_commit [] = $blockIdentifiers [$ci];
		}
		
		//commit blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $blocks_be_commit );
		
		//Get all blocks
		$blockList = $storageClient->getBlockList ( $containerName, $blob_name, 0 );
		$all = $this->stat_num ( $blockList );
		$this->assertEquals ( $numberOfParts, $all, "Type 0 means get all blocks. But here just get commited blocks" );
		
		//Only get commit block
		$blockList = $storageClient->getBlockList ( $containerName, $blob_name, 1 );
		$all = $this->stat_num ( $blockList );
		$this->assertEquals ( $commit_number, $all );
		
		//Only get un_commit block
		$blockList = $storageClient->getBlockList ( $containerName, $blob_name, 2 );
		$all = $this->stat_num ( $blockList );
		$this->assertEquals ( $numberOfParts - $commit_number, $all );
	
	}
	
	/**
	 * Test put blob by put single block and commit blocks.
	 */
	public function testPutBlobInBlockWay() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 10;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		//put 10 blocks
		for($i = 0; $i < $numberOfParts; $i ++) {
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $blob_block_content . '__' . $i );
		}
		
		$commit_number = 3;
		$blocks_be_commit = array ();
		for($ci = 0; $ci < $commit_number; $ci ++) {
			$blocks_be_commit [] = $blockIdentifiers [$ci];
		}
		
		//commit blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $blocks_be_commit );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		$content = "";
		for($i = 0; $i < 3; $i ++) {
			$content = $content . $blob_block_content . '__' . $i;
		}
		$this->assertEquals ( $content, file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
	}
	
	/**
	 * Test
	 * 
	 * step
	 * 1: Put block one by one
	 * 2: put blocks(commit)
	 * 3: get blob and assert it's content
	 * 4: put more blocks and commit
	 * 5: get blob again and assert it's content. ()
	 * 
	 * Conclusion: last 2 step make blob be replaced but not be merged.
	 */
	public function testPutBlobInBlockWayAndPutMoreBlocks() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 3;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		//put 10 blocks
		for($i = 0; $i < $numberOfParts; $i ++) {
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $blob_block_content . '__' . $i );
		}
		
		//commit blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $blockIdentifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$content = "";
		for($i = 0; $i < $numberOfParts; $i ++) {
			$content = $content . $blob_block_content . '__' . $i;
		}
		$this->assertEquals ( $content, file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
		
		$append_block_identifiers = array ();
		$append_number = 2;
		//append two blocks
		for($i = $numberOfParts; $i < $numberOfParts + $append_number; $i ++) {
			$append_block_identifiers [] = $this->generateBlockId ( $i );
		}
		
		//put 2 blocks more
		for($i = $numberOfParts; $i < $numberOfParts + $append_number; $i ++) {
			$storageClient->putBlock ( $containerName, $blob_name, $append_block_identifiers [$i - $numberOfParts], $blob_block_content . '__' . $i );
		}
		
		//commit appended blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $append_block_identifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$content = "";
		for($i = $numberOfParts; $i < $numberOfParts + $append_number; $i ++) {
			$content = $content . $blob_block_content . '__' . $i;
		}
		$this->assertEquals ( $content, file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
	
	}
	
	public function testBlobContentAddByBlockOperation() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 3;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		//put block one by one
		for($i = 0; $i < $numberOfParts; $i ++) {
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $blob_block_content . '__' . $i );
		}
		
		//commit blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $blockIdentifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$content = "";
		for($i = 0; $i < $numberOfParts; $i ++) {
			$content = $content . $blob_block_content . '__' . $i;
		}
		$this->assertEquals ( $content, file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
		
		$append_block_identifiers = array ();
		$append_number = 2;
		//append two blocks
		for($i = $numberOfParts; $i < $numberOfParts + $append_number; $i ++) {
			$append_block_identifiers [] = $this->generateBlockId ( $i );
		}
		
		//put 2 blocks more
		for($i = $numberOfParts; $i < $numberOfParts + $append_number; $i ++) {
			$storageClient->putBlock ( $containerName, $blob_name, $append_block_identifiers [$i - $numberOfParts], $blob_block_content . '__' . $i );
		}
		
		//commit appended blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $append_block_identifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$content = "";
		for($i = $numberOfParts; $i < $numberOfParts + $append_number; $i ++) {
			$content = $content . $blob_block_content . '__' . $i;
		}
		$this->assertEquals ( $content, file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
	
	}
	
	/**
	 * step:
	 * 1: put block with id 0,1,2 one by one
	 * 2: commit
	 * 3: get blob and assert content
	 * 4: put block with id 0,1 again
	 * 5: commit again
	 * 6: get blob and assert content
	 * 
	 */
	public function testBlobContentUpdateByBlockOperation() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 3;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		$block_contents = array ();
		//put block one by one
		for($i = 0; $i < $numberOfParts; $i ++) {
			$temp_content = $blob_block_content . '__' . $i;
			$block_contents [] = $temp_content;
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $temp_content );
		}
		
		//commit blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $blockIdentifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$content = "";
		for($i = 0; $i < $numberOfParts; $i ++) {
			$content = $content . $blob_block_content . '__' . $i;
		}
		$this->assertEquals ( $content, file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
		
		$replace_block_identifiers = array ();
		
		//replace two blocks
		for($i = 0; $i < $numberOfParts; $i ++) {
			$replace_block_identifiers [] = $this->generateBlockId ( $i );
		}
		
		//put 2 blocks with id 0,1 again. Just replace block 0,1
		$after_block_contents = array ();
		for($i = 0; $i < $numberOfParts - 1; $i ++) {
			$new_temp_content = $blob_block_content . '===' . $i;
			$after_block_contents [] = $new_temp_content;
			$storageClient->putBlock ( $containerName, $blob_name, $replace_block_identifiers [$i], $new_temp_content );
		}
		
		//commit replace blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $replace_block_identifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$content = "";
		for($i = 0; $i < $numberOfParts; $i ++) {
			if ($i == $numberOfParts - 1) { //Last block is origin
				$content = $content . $blob_block_content . '__' . $i;
			} else {
				//block 1,2 content are replaced
				$content = $content . $blob_block_content . '===' . $i;
			}
		}
		$this->assertEquals ( $content, file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
	
	}
	
	/**
	 * Test delete part of blob by block operation
	 * step:
	 * 1: put block with id 0,1,2 one by one
	 * 2: commit
	 * 3: get blob and assert content
	 * 4: put block with id 0,1 again
	 * 5: commit 0,1 again
	 * 6: get blob and assert content
	 * 
	 */
	public function testBlobContentDeleteByBlockOperation() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 3;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		$block_contents = array ();
		//put block one by one
		for($i = 0; $i < $numberOfParts; $i ++) {
			$temp_content = $blob_block_content . '__' . $i;
			$block_contents [] = $temp_content;
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $temp_content );
		}
		
		//commit blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $blockIdentifiers );
		
		$reserve_block_identifiers = array ();
		
		//Put only first block again
		$reserve_block_identifiers [] = $this->generateBlockId ( 0 );
		
		//commit the first block
		$storageClient->putBlockList ( $containerName, $blob_name, $reserve_block_identifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$this->assertEquals ( $block_contents [0], file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
	}
	
	/**
	 * Test delete part of blob by block operation
	 * step:
	 * 1: put block with id 0,1,2 one by one
	 * 2: commit 0,1,2
	 * 3: get blob and assert content
	 * 5: commit 2,0,1 again
	 * 6: get blob and assert content
	 * 
	 */
	public function testBlobContentSortByBlockOperation() {
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createStorageClient ();
		
		$storageClient->createContainer ( $containerName );
		
		$blob_name = "bepartblob";
		
		$blob_block_content = "Test blob block";
		
		$numberOfParts = 3;
		// Generate block id's
		$blockIdentifiers = array ();
		for($i = 0; $i < $numberOfParts; $i ++) {
			$blockIdentifiers [] = $this->generateBlockId ( $i );
		}
		
		$block_contents = array ();
		//put block one by one
		for($i = 0; $i < $numberOfParts; $i ++) {
			$temp_content = $blob_block_content . '__' . $i;
			$block_contents [] = $temp_content;
			$storageClient->putBlock ( $containerName, $blob_name, $blockIdentifiers [$i], $temp_content );
		}
		
		//commit blocks
		$storageClient->putBlockList ( $containerName, $blob_name, $blockIdentifiers );
		
		$reserve_block_identifiers = array ();
		
		//put block as 2,0,1 
		$reserve_block_identifiers [] = $this->generateBlockId ( 2 );
		$reserve_block_identifiers [] = $this->generateBlockId ( 0 );
		$reserve_block_identifiers [] = $this->generateBlockId ( 1 );
		
		//commit the first block
		$storageClient->putBlockList ( $containerName, $blob_name, $reserve_block_identifiers );
		
		//get blob
		$temp_file = "blockfile";
		$storageClient->getBlob ( $containerName, $blob_name, $temp_file );
		
		$this->assertEquals ( $block_contents [2] . $block_contents [0] . $block_contents [1], file_get_contents ( $temp_file ) );
		unlink ( $temp_file );
	}
	
	protected function stat_num($array) {
		$stat = 0;
		foreach ( $array as $child ) {
			$stat = $stat + sizeof ( $child );
		}
		return $stat;
	}
	
	/**
	 * Generate block id
	 * 
	 * @param int $part Block number
	 * @return string Windows Azure Blob Storage block number
	 */
	protected function generateBlockId($part = 0) {
		$returnValue = $part;
		while ( strlen ( $returnValue ) < 64 ) {
			$returnValue = '0' . $returnValue;
		}
		
		return $returnValue;
	}
	
	/**
	 * The data should be parsed at the same time when it's being downloaded from azure server.
	 *
	 */
	public function testParseDataWhenDownload() {
		$this->fail ( "API not support" );
	}
}

// Call Microsoft_WindowsAzure_BlobStorageTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_WindowsAzure_BlobTest::main") {
	Microsoft_WindowsAzure_BlobTest::main ();
}
	
