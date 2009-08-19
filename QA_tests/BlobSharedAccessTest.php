<?php
if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_BlobSharedAccessTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'Microsoft/Azure/Storage/Blob.php';

require_once 'Microsoft/Azure/SharedAccessSignatureCredentials.php';

class Microsoft_Azure_BlobSharedAccessTest extends PHPUnit_Framework_TestCase {
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
			$adminClient = $this->_createAdminStorageClient ();
			foreach ( $this->_tempContainers as $container )
				try {
					$adminClient->deleteContainer ( $container );
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
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_BlobSharedAccessTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	private function _createAdminStorageClient() {
		return new Microsoft_Azure_Storage_Blob ( BLOB_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN ( 10, 250 ) );
	}
	
	private function _createGuestStorageClient() {
		$storageClient = new Microsoft_Azure_Storage_Blob ( BLOB_HOST, CLIENT_STORAGE_ACCOUNT, CLIENT_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN ( 10, 250 ) );
		$storageClient->setCredentials ( new Microsoft_Azure_SharedAccessSignatureCredentials ( CLIENT_STORAGE_ACCOUNT, CLIENT_STORAGE_KEY, false ) );
		return $storageClient;
	}
	
	private function _createTempFile($content) {
		$fileName = tempnam ( '', 'tst' );
		$fp = fopen ( $fileName, 'w' );
		fwrite ( $fp, $content );
		fclose ( $fp );
		array_push ( $this->_tempFiles, $fileName );
		return $fileName;
	}
	
	public function testInvalidPermission() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, '', 'c', 'w', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$result = $adminClient->containerExists ( $containerName );
		$this->assertTrue ( $result );
		
		$exception = null;
		try {
			$guestClient->listBlobs ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "Doesn't have permission to listblobs" );
	}
	
	/**
	 * Specifying Permissions
	 *
	 *The permissions specified on the URL indicate which operations are permitted on which resource. 
	 *Supported permissions include read (r), write (w), delete (d), and list (l).
	 *
	 *Permissions may be grouped so as to allow multiple operations to be performed with the given signature. 
	 *For example, to grant all permissions to the signed resource, the URL must specify sp=rwdl. 
	 *To grant only read/write permissions, the URL must specify sp=rw.
	 *
	 */
	public function testInvalidPermissionUnOrderRWDL() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, '', 'c', 'rdlw', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		
		/**
		 * Maybe the serPermissionSet should bound to storage client directly.
		 */
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$exception = null;
		try {
			$guestClient->listBlobs ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "Shared accesss url is invalid." );
		$this->assertTrue ( strpos ( $exception->getMessage (), "Server failed to authenticate the request" ) !== FALSE );
	}
	
	/**
	 * Test time expired when use 'Share Access Credential'
	 *
	 */
	public function testInvalidPermissionTimeExpired() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 30 ); // Lifetime for 30 second.
		

		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, '', 'c', 'l', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$exception = null;
		try {
			$guestClient->listBlobs ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNull ( $exception, "Shared access operation ok." );
		
		$this->_wait ( 30 );
		
		$exception = null;
		try {
			$guestClient->listBlobs ( $containerName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "Shared access operation not ok for time expire." );
	}
	
	protected function _wait($time = 30) {
		sleep ( $time );
	}
	
	/**
	 * Supported operations include: 
	 * Reading and writing blob content, block lists, properties, and metadata 
	 * Deleting a blob 
	 * Listing the blobs within a containe
	 *
	 */
	public function testContainerValidOperations() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		//Grant all priveliges to container
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, '', 'c', 'rwdl', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$blob_name = "temp_blob.txt";
		$local_file_name = "temp_blob_file";
		$content = "I love azure";
		$temp_file = $this->_createTempFile ( $content );
		
		$exception = null;
		try {
			//put blob, get blob and delete blob are allow.
			$guestClient->putBlob ( $containerName, $blob_name, $temp_file );
			$guestClient->getBlob ( $containerName, $blob_name, $local_file_name );
			echo file_get_contents ( $local_file_name );
			$this->assertEquals ( $content, file_get_contents ( $local_file_name ) );
			unlink ( $local_file_name );
			//Delete blob is allow
			$guestClient->deleteBlob ( $containerName, $blob_name );
			// put block, get blocks are allow.
			$guestClient->putBlock ( $containerName, $blob_name, "identifier", $content );
			//commit 
			$guestClient->putBlockList ( $containerName, $blob_name, array ("identifier" ) );
			$guestClient->getBlob ( $containerName, $blob_name, $local_file_name );
			$this->assertEquals ( $content, file_get_contents ( $local_file_name ) );
			unlink ( $local_file_name );
			//Lost blob operation is also support
			$blobs = $guestClient->listBlobs ( $containerName );
			$this->assertTrue ( sizeof ( $blobs ) > 0 );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNull ( $exception );
	}
	
	/**
	 * Supported operations include: 
	 * Reading and writing blob content, block lists, properties, and metadata 
	 * Deleting a blob 
	 * Listing the blobs within a containe
	 *
	 */
	public function testContainerInvalidOperations() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		//Grant all priveliges to container
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, '', 'c', 'rwdl', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$blob_name = "temp_blob.txt";
		$content = "I love azure";
		$temp_file = $this->_createTempFile ( $content );
		
		$exception = null;
		
		//allow 
		try {
			//put blob, get blob and delete blob are allow.
			$guestClient->putBlob ( $containerName, $blob_name, $temp_file );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNull ( $exception );
		
		//Not allow.(Copy blob)
		$exception = null;
		try {
			//put blob, get blob and delete blob are allow.
			$guestClient->copyBlob ( $containerName, $blob_name, $containerName, 'new' . $blob_name );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		//Error message like this:  The copy source account and destination account must be the same.
		$this->assertNotNull ( $exception );
		$this->assertTrue ( strpos ( $exception->getMessage (), "The copy source account and destination account must be the same." ) !== false );
	}
	
	/**
	 * Test set container acl advanced
	 */
	public function testSetContainerAclAdvanced() {
		
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createAdminStorageClient ();
		$storageClient->createContainer ( $containerName );
		
		$id = 'ABCD';
		$permission = 'rwdl';
		$storageClient->setContainerAcl ( $containerName, Microsoft_Azure_Storage_Blob::ACL_PRIVATE, 
				array ( new Microsoft_Azure_Storage_SignedIdentifier($id, '2009-10-18', '2009-10-21', $permission) ) );
		$acl = $storageClient->getContainerAcl ( $containerName, true );

		$this->assertTrue(count($acl) == 1);
		$this->assertEquals($id, "". $acl[0]->id );
		$this->assertEquals($permission,  "". $acl[0]->permissions );
		$this->assertTrue( strpos( "". $acl[0]->start,  '2009-10-18') !== FALSE );
		$this->assertTrue( strpos( "". $acl[0]->expiry,  '2009-10-21') !== FALSE );
		
		
		// test update acl
		$storageClient->setContainerAcl ( $containerName, Microsoft_Azure_Storage_Blob::ACL_PRIVATE, 
				array ( new Microsoft_Azure_Storage_SignedIdentifier($id, '2009-10-18', '2009-10-21', 'r') ) );
		$acl = $storageClient->getContainerAcl ( $containerName, true );
		$this->assertEquals("r",  "". $acl[0]->permissions );
		
		// test add acl
		$storageClient->setContainerAcl ( $containerName, Microsoft_Azure_Storage_Blob::ACL_PRIVATE, 
				array ( new Microsoft_Azure_Storage_SignedIdentifier($id, '2009-10-18', '2009-10-21', 'rd'),
						new Microsoft_Azure_Storage_SignedIdentifier('ABCDE', '2009-10-18', '2009-10-21', $permission)	) );
		$acl = $storageClient->getContainerAcl ( $containerName, true );
		$this->assertTrue(count($acl) == 2);
		
		// test delete 		
		$storageClient->setContainerAcl ( $containerName, Microsoft_Azure_Storage_Blob::ACL_PRIVATE, array () );
		$acl = $storageClient->getContainerAcl ( $containerName, true );
		$this->assertTrue(count($acl) == 0);
		
	}
	
/**
	 * R:
	 * Supported operations include  Get Blob,  Get Block List,  Get Blob Properties, and  Get Blob Metadata.
	 * W:
	 * Supported operations include  Put Blob,  Put Block,  Put Block List, and  Set Blob Metadata.
	 * D:
	 * Supported operations include  Delete Blob.
	 * L:
	 * No operation
	 *
	 */
	public function testBlobValidOperations() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$blob_name = "temp_blob.txt";
		$content = "I love azure";
		$temp_file = $this->_createTempFile ( $content );
		
		$adminClient->putBlob ( $containerName, $blob_name, $temp_file );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		//Grant all priveliges to container
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, $blob_name, 'b', 'rwdl', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$exception = null;
		try {
			$local_file_name = "local_blob_file";
			//get blob
			$guestClient->getBlob ( $containerName, $blob_name, $local_file_name );
			$this->assertEquals ( $content, file_get_contents ( $local_file_name ) );
			unlink ( $local_file_name );
			//Delete blob is allow
			$guestClient->deleteBlob ( $containerName, $blob_name );
			// put block, get blocks are allow.
			$guestClient->putBlock ( $containerName, $blob_name, "identifier", $content );
			//commit 
			$guestClient->putBlockList ( $containerName, $blob_name, array ("identifier" ) );
			$guestClient->getBlob ( $containerName, $blob_name, $local_file_name );
			$this->assertEquals ( $content, file_get_contents ( $local_file_name ) );
			unlink ( $local_file_name );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNull ( $exception );
	}
	
	/**
	 * R:
	 * Supported operations include  Get Blob,  Get Block List,  Get Blob Properties, and  Get Blob Metadata.
	 * W:
	 * Supported operations include  Put Blob,  Put Block,  Put Block List, and  Set Blob Metadata.
	 * D:
	 * Supported operations include  Delete Blob.
	 * L:
	 * No operation
	 *
	 */
	public function testBlobInvalidOperations() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$blob_name = "temp_blob.txt";
		$content = "I love azure";
		$temp_file = $this->_createTempFile ( $content );
		
		$adminClient->putBlob ( $containerName, $blob_name, $temp_file );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		//Grant all priveliges to container
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, $blob_name, 'b', 'rwdl', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$exception = null;
		try {
			$local_file_name = "local_blob_file";
			//get blob
			$guestClient->getBlob ( $containerName, $blob_name, $local_file_name );
			$this->assertEquals ( $content, file_get_contents ( $local_file_name ) );
			unlink ( $local_file_name );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNull ( $exception );
		
		$exception = null;
		try {
			//List blob operation is also support
			$blobs = $guestClient->listBlobs ( $containerName );
			$this->assertTrue ( sizeof ( $blobs ) > 0 );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull ( $exception, "Have no privileges to 'list' container." );
	}
	
	/**
	 * Even not list operation on blob, you can also add 'l' to sp fragment.
	 *
	 */
	public function testBlobAddLPermision() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$blob_name = "temp_blob.txt";
		$content = "I love azure";
		$temp_file = $this->_createTempFile ( $content );
		
		$adminClient->putBlob ( $containerName, $blob_name, $temp_file );
		
		$start = $adminClient->isoDate ( time () );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		//Grant all priveliges to container
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, $blob_name, 'b', 'rwdl', $start, $expiry );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		$exception = null;
		try {
			$local_file_name = "tets_blob";
			$guestClient->getBlob ( $containerName, $blob_name, $local_file_name );
			unlink ( $local_file_name );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNull ( $exception );
	}
	
	/**
	 * Access without signed identifier cannot have time window more than 1 hour
	 *
	 */
	public function testInvalidTimeWindow(){
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl( $containerName, '', 'c', 'w', '2009-10-18', '2009-10-21'	);
	
		$guestClient = $this->_createGuestStorageClient();
        $credentials = $guestClient->getCredentials();
        $credentials->setPermissionSet(array(   $sharedAccessUrl  ));
        
		$exception = null;
        try {			
			$guestClient->listBlobs($containerName);
		} catch ( Exception $ex ) {
			$exception = $ex;			
		}
		$this->assertNotNull($exception, "Access without signed identifier cannot have time window more than 1 hour");
	}
	
	/**
	 * Note that at most five separate access policies can be set for a given container at any time. 
	 * If more than five access policies are passed in the request body, then the service returns status code 400 (Bad Request).
	 *
	 */
	public function testSetContainerAcl_InvalidPolicy(){
		$policies = array(new Microsoft_Azure_Storage_SignedIdentifier( 'ABCD', '2009-10-18', '2009-10-21',  'rwdl'),
			new Microsoft_Azure_Storage_SignedIdentifier( 'ABCDE', '2009-10-18', '2009-10-21',  'rwdl'),
			new Microsoft_Azure_Storage_SignedIdentifier( 'ABCDF', '2009-10-18', '2009-10-21',  'rwdl'),
			new Microsoft_Azure_Storage_SignedIdentifier( 'ABCDG', '2009-10-18', '2009-10-21',  'rwdl'),
			new Microsoft_Azure_Storage_SignedIdentifier( 'ABCDH', '2009-10-18', '2009-10-21',  'rwdl'),
			new Microsoft_Azure_Storage_SignedIdentifier( 'ABCDI', '2009-10-18', '2009-10-21',  'rwdl'),
		);
		
		$containerName = $this->getContainerName ();
		$storageClient = $this->_createAdminStorageClient ();
		$storageClient->createContainer ( $containerName );
		
		// test 6 policies
		$exception = null;
        try {			
			$storageClient->setContainerAcl ( $containerName, Microsoft_Azure_Storage_Blob::ACL_PRIVATE, $policies	 );
		} catch ( Exception $ex ) {
			$exception = $ex;			
		}
		$this->assertNotNull($exception, "Only five access policies is allowed");
		
		// test 5 policies
		array_pop($policies);
		$exception = null;
        try {			
			$storageClient->setContainerAcl ( $containerName, Microsoft_Azure_Storage_Blob::ACL_PRIVATE, $policies	 );
		} catch ( Exception $ex ) {
			$exception = $ex;			
		}
		$this->assertNull($exception, "Five access policies is allowed");
		
	}
	
	public function testContainerPolicy() {
		$containerName = $this->getContainerName ();
		$adminClient = $this->_createAdminStorageClient ();
		$adminClient->createContainer ( $containerName );
		
		$start = $adminClient->isoDate ( time () - 50 );
		$expiry = $adminClient->isoDate ( time () + 3000 );
		
		$adminClient->setContainerAcl ( $containerName, Microsoft_Azure_Storage_Blob::ACL_PRIVATE, array ( new Microsoft_Azure_Storage_SignedIdentifier('ABCD', $start, $expiry, 'rwdl') ) );		
		$sharedAccessUrl = $adminClient->generateSharedAccessUrl ( $containerName, '', 'c', 'r', $start, $expiry, 'ABCD' );
		
		$guestClient = $this->_createGuestStorageClient ();
		$credentials = $guestClient->getCredentials ();
		$credentials->setPermissionSet ( array ($sharedAccessUrl ) );
		
		// test list blobs
		$guestClient->listBlobs ( $containerName );
	}
	

}

// Call Microsoft_Azure_BlobSharedAccessTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_BlobSharedAccessTest::main") {
	Microsoft_Azure_BlobTest::main ();
}

?>