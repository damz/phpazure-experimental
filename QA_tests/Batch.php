<?php
if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_BlobTest::main' );
}

require_once 'PHPUnit/Framework.php';

/** Microsoft_WindowsAzure_Storage_Table */
require_once 'Microsoft/WindowsAzure/Storage/Table.php';

class Microsoft_WindowsAzure_BlobTest extends PHPUnit_Framework_TestCase {
	static $path;
	
	protected $_tempFiles = array ();
	
	protected $_tempContainers = array ();
	
	protected $storage;
	
	protected static $uniqId = 0;
	
	protected function generateName() {
		self::$uniqId ++;
		return TESTS_TABLE_TABLENAME_PREFIX . self::$uniqId;
	}
	
	public function __construct() {
		require_once 'TestConfiguration.php';
		self::$path = dirname ( __FILE__ ) . '/datas/';
	}
	
	protected function setUp() {
		require_once 'TestConfiguration.php';
	}
	
	private function _createStorageClient() {
		return new Microsoft_WindowsAzure_Storage_Blob ( TABLE_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_WindowsAzure_RetryPolicy::retryN ( 10, 250 ) );
	}
	
	public function testDeleteEntityInBatch() {
		$tableName = $this->generateName ();
		$storageClient = $this->createStorageInstance ();
		$storageClient->createTable ( $tableName );
		
		$entities = $this->_generateEntities ( 2 );
		$entity = $entities [0];
		
		$storageClient->insertEntity ( $tableName, $entities [0] );
		$storageClient->insertEntity ( $tableName, $entities [1] );
		
		// Start batch
		$batch = $storageClient->startBatch ();
		$this->assertType ( 'Microsoft_WindowsAzure_Storage_Batch', $batch );
		
		// Insert entities in batch
		foreach ( $entities as $entity ) {
			$storageClient->deleteEntity ( $tableName, $entity );
		}
		
		// Commit
		$batch->commit ();
		
		// Verify
		$result = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( 0, count ( $result ) );
	
	}
	
	/**
	 * Generate entities
	 * 
	 * @param int 		$amount Number of entities to generate
	 * @return array 			Array of TSTest_TestEntity
	 */
	protected function _generateEntities($amount = 1) {
		$returnValue = array ();
		
		for($i = 0; $i < $amount; $i ++) {
			$entity = new TSTest_TestEntity ( 'partition1', 'row' . ($i + 1) );
			$entity->FullName = md5 ( uniqid ( rand (), true ) );
			$entity->Age = rand ( 1, 130 );
			$entity->Visible = true;
			
			$returnValue [] = $entity;
		}
		
		return $returnValue;
	}
	
protected function createStorageInstance()
    {
        $storageClient = null;
        if (TESTS_TABLE_RUNONPROD)
        {
            $storageClient = new Microsoft_WindowsAzure_Storage_Table(TESTS_TABLE_HOST_PROD, TESTS_STORAGE_ACCOUNT_PROD, TESTS_STORAGE_KEY_PROD, false, Microsoft_WindowsAzure_RetryPolicy::retryN(10, 250));
        }
        else
        {
            $storageClient = new Microsoft_WindowsAzure_Storage_Table(TESTS_TABLE_HOST_DEV, TESTS_STORAGE_ACCOUNT_DEV, TESTS_STORAGE_KEY_DEV, true, Microsoft_WindowsAzure_RetryPolicy::retryN(10, 250));
            $storageClient->setOdbcSettings(TESTS_TABLE_DEVCNSTRING, TESTS_TABLE_DEVCNUSER, TESTS_TABLE_DEVCNPASS);
        }
        
        if (TESTS_STORAGE_USEPROXY)
        {
            $storageClient->setProxy(TESTS_STORAGE_USEPROXY, TESTS_STORAGE_PROXY, TESTS_STORAGE_PROXY_PORT, TESTS_STORAGE_PROXY_CREDENTIALS);
        }

        return $storageClient;
    }

}

/**
 * Test Microsoft_WindowsAzure_Storage_TableEntity class
 */
class TSTest_TestEntity extends Microsoft_WindowsAzure_Storage_TableEntity {
	/**
	 * @azure Name
	 */
	public $FullName;
	
	/**
	 * @azure Age Edm.Int64
	 */
	public $Age;
	
	/**
	 * @azure Visible Edm.Boolean
	 */
	public $Visible = false;
}
