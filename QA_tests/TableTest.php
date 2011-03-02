<?php

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_TableTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'Microsoft/WindowsAzure/Storage/Table.php';
require_once 'TestTableEntity.php';

class Microsoft_WindowsAzure_TableTest extends PHPUnit_Framework_TestCase {
	
	protected static $tablePrefix = "phptabletest";
	
	protected static $partitionKeyPrefix = "partition";
	protected static $rowKeyPrefix = "row";
	
	protected static $uniqId = 0;
	
	protected $_tempTables = array ();
	
	protected function generateTableName() {
		self::$uniqId ++;
		$name = self::$tablePrefix . self::$uniqId;
		$this->_tempTables [] = $name;
		return $name;
	}
	
	public function __construct() {
		require_once 'TestConfiguration.php';
		self::$uniqId = mt_rand ( 0, 10000 );
	}
	
	/**
	 * Test setup
	 */
	protected function setUp() {
	}
	
	/**
	 * Test teardown
	 */
	protected function tearDown() {
		if (count ( $this->_tempTables ) > 0) {
			$storageClient = $this->_createStorageClient ();
			foreach ( $this->_tempTables as $table )
				try {
					$storageClient->deleteTable ( $table );
				} catch ( Exception $e ) {
					// ignore
				}
			
			$this->_tempTables = array ();
		}
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_WindowsAzure_TableTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	protected function _createStorageClient() {
		return new Microsoft_WindowsAzure_Storage_Table ( TABLE_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_WindowsAzure_RetryPolicy::retryN ( 10, 250 ) );
	}
	
	protected function _generateEntities($count, $partition_increment = false, $row_increment = true) {
		if ($count <= 1) {
			$count = 1;
		}
		$eitities = array ();
		for($i = 1; $i <= $count; $i ++) {
			$eitities [] = new Test_TableEntity ( $partition_increment ? self::$partitionKeyPrefix . $i : self::$partitionKeyPrefix, $row_increment ? self::$rowKeyPrefix . $i : self::$rowKeyPrefix );
		}
		if ($count <= 1) {
			return $eitities [0];
		} else
			return $eitities;
	}
	
	protected function _tableExists($result, $tableName) {
		foreach ( $result as $table )
			if ($table->Name == $tableName) {
				return true;
			}
		return false;
	}
	
	protected function _randomString($length = 1) {
		$char = array ();
		for($i = 0; $i < $length; $i ++)
			$char [] = chr ( rand ( 65, 90 ) );
		return implode ( "", $char );
	}
	
	protected function _randomFloat($min, $max) {
		return ($min + lcg_value () * (abs ( $max - $min )));
	}
	
	/**
     * Test table exists
     */
    public function testTableExists()
    {
    	$storageClient = $this->_createStorageClient ();
    	$tableName = $this->generateTableName ();
    	$storageClient->createTable ( $tableName );
    	
    	$result = $storageClient->tableExists($tableName);
        $this->assertTrue($result);
        
        // test after delete table
        $storageClient->deleteTable ( $tableName );
        $result = $storageClient->tableExists($tableName);
        $this->assertFalse($result);
        
        $result = $storageClient->tableExists("notexiststable");
        $this->assertFalse($result);
    }
    
	/**
	 * Test create table
	 */
	public function testCreateTable() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		
		try {
			$result = $storageClient->createTable ( $tableName );
			$this->assertEquals ( $tableName, $result->Name );
			
			$result = $storageClient->listTables ();
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
		
		$this->assertTrue ( count ( $result ) > 0 );
		
		$this->assertTrue ( $this->_tableExists ( $result, $tableName ) );
	
	}
	
	/**
	 * Test create table
	 */
	public function testCreateTable_Conflict() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		
		$exception = null;
		try {
			$storageClient->createTable ( $tableName );
			$storageClient->createTable ( $tableName );		
		} catch ( Exception $ex ) {
			$exception = $ex;			
		}
	 	$this->assertNotNull($exception, "table alread exists");
  		$this->assertEquals("The table specified already exists.", $exception->getMessage());
	}
	
	/**
	 * Test list tables
	 */
	public function testListTables() {
		$storageClient = $this->_createStorageClient ();
		$tableName1 = $this->generateTableName ();
		$tableName2 = $this->generateTableName ();
		try {
			$storageClient->createTable ( $tableName1 );
			$storageClient->createTable ( $tableName2 );
			$result = $storageClient->listTables ();
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
		$this->assertTrue ( count ( $result ) > 2 );
		$this->assertTrue ( $this->_tableExists ( $result, $tableName1 ) );
		$this->assertTrue ( $this->_tableExists ( $result, $tableName2 ) );
	}
	
	/**
	 * Test delete table
	 */
	public function testDeleteTable() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		
		try {
			$storageClient->createTable ( $tableName );
			
			$result = $storageClient->listTables ();
			$this->assertTrue ( $this->_tableExists ( $result, $tableName ) );
			
			$storageClient->deleteTable ( $tableName );
			
			$result = $storageClient->listTables ();
			$this->assertFalse ( $this->_tableExists ( $result, $tableName ) );
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
	}
	
	public function testDeleteTable_NotExists() {
		$tableName = "notexisttable";
		$storageClient = $this->_createStorageClient ();
		
		$exception = null;
		try {
			$storageClient->deleteTable ( $tableName );			
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull($exception, "table does not exist");
  		$this->assertEquals("The specified resource does not exist.", $exception->getMessage());
		
	}
	
	public function testInsertEntity() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = $this->_generateEntities ( 1 );
		$storageClient->insertEntity ( $tableName, $entity );
		$result = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( 1, sizeof ( $result ) );
	}
	
	/**
	 * Can't insert a boolean field with false value.
	 *
	 */
	public function testInsertEntityWithFalse() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		try {
			$entity = new Boolean_TableEntity ( "part", "row" );
			$entity->booleanField = false;
			$storageClient->insertEntity ( $tableName, $entity );
			$result = $storageClient->retrieveEntities ( $tableName );
			$e = $result [0];
			$this->assertEquals ( 'true', $e->booleanField );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testInsertEntity_Multiple() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 3;
		$entities = $this->_generateEntities ( $count );
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$result = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( $count, sizeof ( $result ) );
	}
	
	public function testInsertEntity_LargeStringField() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$string = str_repeat ( "hello ketty", 1 );
		$entity->stringField = $string;
		$storageClient->insertEntity ( $tableName, $entity );
		$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
		$one = $result [0];
		$this->assertTrue ( $one->stringField == $string );
	}
	
	/**
	 * The entity can't be inserted with a null field.
	 *
	 */
	public function testInsertEntity_NullField() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$entity->stringField = null;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
		} catch ( Exception $e ) {
			$this->assertTrue ( get_class ( $e ) == Mircosoft_Azure_Exception );
		}
	}
	
	public function testInsertEntity_Int32Field() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$s = str_repeat ( "1", 10 );
		$invalue = ( int ) $s;
		$entity->int32Field = $invalue;
		$storageClient->insertEntity ( $tableName, $entity );
		$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
		$one = $result [0];
		$this->assertTrue ( $one->int32Field == $invalue );
	}
	
	public function testInsertEntity_Int32FieldTypeUnMatch() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$invalue = 0.04;
		$entity->int32Field = $invalue;
		$exception = null;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->assertNotNull($exception, "Field value does not match its type");
		$this->_assertMircosoftAzureException ( $exception );
	}
	
	protected function _assertMircosoftAzureException($e) {
		$this->assertEquals ( 'Microsoft_WindowsAzure_Exception', get_class ( $e ) );
	}
	
	public function testInsertEntity_DateTimeFieldInGMTFormat() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$dateTime = $this->_gmtTimeFormat ();
		$entity->dateField = $dateTime;
		$exception = null;
		try {
			$storageClient->insertEntity ( $tableName, $entity );		
		} catch ( Exception $e ) {
			$exception = $e;		
		}
		$this->assertNotNull($exception, "Time format is not supported" );
	}
	
	/**
	 * Only ISO date format is suopport.
	 *
	 */
	public function testInsertEntity_DateTimeFieldInISOFormat() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entitiy = ($this->_generateEntities ( 1 ));
		$dateTime = $this->_isoDate ();
		$entitiy->dateField = $dateTime;
		$storageClient->insertEntity ( $tableName, $entitiy );
		$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
		$one = $result [0];
		$this->assertTrue ( $one->dateField == $dateTime );
	}
	
	/**
	 * Date time format should be done internal. I hope a date() object can be supported for Edm.DateField
	 *  
	 * Get an error here.
	 */
	public function _testInsertEntiy_DateTimeFieldDateObject() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entitiy = ($this->_generateEntities ( 1 ));
		$dateTime = $this->_gmtTime ();
		$entitiy->dateField = $dateTime;
		$exception = null;
		try {
			$storageClient->insertEntity ( $tableName, $entitiy );
		} catch ( Exception $e ) {
			$exception = $e;	
		}
		$this->assertNotNull($exception);
	}
	
	public function testUpdateEntity_ValidField() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$invalue = 123456;
		$entity->int32Field = $invalue;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
			$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
			$one = $result [0];
			$this->assertEquals ( $entity->int32Field, $one->int32Field );
			$entity->int32Field = 555666;
			$storageClient->updateEntity ( $tableName, $entity );
			$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
			$one = $result [0];
			$this->assertEquals ( $entity->int32Field, $one->int32Field );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testUpdateEntity_InvalidField() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$invalue = 123456;
		$entity->int32Field = $invalue;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
			$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
			$one = $result [0];
			$this->assertEquals ( $entity->int32Field, $one->int32Field );
			$entity->int32Field = 0.0005;
			try {
				$storageClient->updateEntity ( $tableName, $entity );
			} catch ( Exception $e ) {
				$this->_assertMircosoftAzureException ( $e );
				$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
				$one = $result [0];
				$this->assertEquals ( $invalue, $one->int32Field );
			}
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Even the verify etag is not setting, you also can't change the etag by your self.
	 *
	 */
	public function testUpdateEntity_EtagChanged_NotVerifyEtag() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$invalue = 123456;
		$entity->int32Field = $invalue;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
			$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
			$one = $result [0];
			$this->assertEquals ( $entity->int32Field, $one->int32Field );
			$entity->int32Field = 88888;
			$etag = $entity->getEtag ();
			$newTagValue = 'newTagValue';
			$entity->setEtag ( $newTagValue );
			$storageClient->updateEntity ( $tableName, $entity, false );
			$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
			$one = $result [0];
			$this->assertEquals ( $entity->int32Field, $one->int32Field );
			//The eTag value is changed if update successfully
			$this->assertNotEquals ( $etag, $one->getEtag () );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testUpdateEntity_EtagChanged_VerifyEtag() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entity = ($this->_generateEntities ( 1 ));
		$invalue = 123456;
		$entity->int32Field = $invalue;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
			$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity", true );
			$one = $result [0];
			$this->assertEquals ( $entity->int32Field, $one->int32Field );
			$entity->int32Field = 88888;
			$etag = $entity->getEtag ();
			$newTagValue = 'newTagValue';
			$entity->setEtag ( $newTagValue );
			try {
				$storageClient->updateEntity ( $tableName, $entity, true );
				$this->fail ( "Can't update with different etag if the verify etag is setted to true" );
			} catch ( Exception $e ) {
				$this->_assertMircosoftAzureException ( $e );
				$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
				$one = $result [0];
				//The value should not ne changed
				$this->assertEquals ( $invalue, $one->int32Field );
				$this->assertEquals ( $etag, $one->getEtag () );
			}
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testBatch_Rollback() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 2;
		$entities = $this->_generateEntities ( $count );
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
		// Start batch
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$storageClient->deleteEntity ( $tableName, $entity );
		}
		
		// Rollback
		$batch->rollback ();
		$result = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( $count, sizeof ( $result ) );
	}
	
	public function testBatch_Insert() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 3;
		
		// Start batch
		$batch = $storageClient->startBatch ();
		$entities = $this->_generateEntities ( $count );
		$map = array ();
		foreach ( $entities as $entity ) {
			$entity->int32Field = mt_rand ( 10, 10000 );
			$entity->stringField = $this->_randomString ( 10 );
			$storageClient->insertEntity ( $tableName, $entity );
			$map [$entity->getPartitionKey () . "|" . $entity->getRowKey ()] = $entity;
		}
		
		// Commit
		$batch->commit ();
		$result = $storageClient->retrieveEntities ( $tableName, "Test_TableEntity" );
		$this->assertEquals ( $count, sizeof ( $result ) );
		foreach ( $result as $entity ) {
			$e = $map [$entity->getPartitionKey () . "|" . $entity->getRowKey ()];
			$this->assertEquals ( $e->int32Field, $entity->int32Field );
			$this->assertEquals ( $e->stringField, $entity->stringField );
		}
	}
	
	public function testBatch_Update() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 3;
		
		$entities = $this->_generateEntities ( $count );
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
		$map = array ();
		// Start batch update
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$entity->int32Field = mt_rand ( 10, 10000 );
			$entity->stringField = $this->_randomString ( 10 );
			$storageClient->updateEntity ( $tableName, $entity );
			$map [$entity->getPartitionKey () . "|" . $entity->getRowKey ()] = $entity;
		}
		
		// Commit
		$batch->commit ();
		
		$result = $storageClient->retrieveEntities ( $tableName, "Test_TableEntity" );
		$this->assertEquals ( $count, sizeof ( $result ) );
		foreach ( $result as $entity ) {
			$e = $map [$entity->getPartitionKey () . "|" . $entity->getRowKey ()];
			$this->assertEquals ( $e->int32Field, $entity->int32Field );
			$this->assertEquals ( $e->stringField, $entity->stringField );
		}
	}
	
	public function testBatch_Delete() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 3;
		
		$entities = $this->_generateEntities ( $count );
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$result = $storageClient->retrieveEntities ( $tableName, "Test_TableEntity" );
		$this->assertEquals ( $count, sizeof ( $result ) );
		
		// Start batch delete
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$storageClient->deleteEntity ( $tableName, $entity );
		}
		
		// Commit
		$batch->commit ();
		$result = $storageClient->retrieveEntities ( $tableName, "Test_TableEntity" );
		$this->assertEquals ( 0, sizeof ( $result ) );
	}
	
	public function testBatch_InsertUpdateDelete() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 3;
		
		$entities = $this->_generateEntities ( $count );
		$storageClient->insertEntity ( $tableName, $entities [0] );
		$storageClient->insertEntity ( $tableName, $entities [1] );
		
		// Start batch delete
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entities [2] );
			$storageClient->deleteEntity ( $tableName, $entities [1] );
			$entities [0]->int64Field = mt_rand ( 10, 10000 );
			$entities [0]->doubleField = $this->_randomFloat ( 10, 100000 );
			$storageClient->updateEntity ( $tableName, $entities [0] );
		}
		// Commit
		$batch->commit ();
		
		$result = $storageClient->retrieveEntities ( $tableName, "Test_TableEntity" );
		$this->assertEquals ( 2, sizeof ( $result ) );
		foreach ( $entities as $entity )
			if ($entity->getPartitionKey () == $entities [0]->getPartitionKey () && $entity->getRowKey () == $entities [0]->getRowKey ()) {
				$this->assertEquals ( $entities [0]->int64Field, $entity->int64Field );
				$this->assertEquals ( $entities [0]->doubleField, $entity->doubleField );
			}
	
	}
	
	public function testBatch_FailPartitionKey() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 2;
		$entities = $this->_generateEntities ( $count, true );
		
		try {
			// Start batch
			$batch = $storageClient->startBatch ();
			
			foreach ( $entities as $entity ) {
				$storageClient->insertEntity ( $tableName, $entity );
			}
			// Commit
			$batch->commit ();
			$this->fail ( "Entities in batch must have same partitionkey" );
		} catch ( Exception $ex ) {
			$this->assertEquals ( "An error has occured while committing a batch: 1:All commands in a batch must operate on same entity group.", $ex->getMessage () );
		}
		$result = $storageClient->retrieveEntities ( $tableName, "Test_TableEntity" );
		$this->assertEquals ( 0, sizeof ( $result ) );
	}
	
	/**
	 * An entity can appear only once in the transaction, and only one operation may be performed against it. 
	 *
	 */
	public function testBatch_FailEntity() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 1;
		
		$entity = $this->_generateEntities ( $count, true );
		
		$exception = null;
		try {
			// Start batch
			$batch = $storageClient->startBatch ();
			
			$storageClient->insertEntity ( $tableName, $entity );
			$storageClient->deleteEntity ( $tableName, $entity );
			// Commit
			$batch->commit ();
		} catch ( Exception $ex ) {
			$exception = $ex;			
		}
	   $this->assertNotNull($exception, "An entity can appear only once in the transaction, and only one operation may be performed against it. " );
	   $this->assertEquals("An error has occured while committing a batch: 0:One of the request inputs is not valid.", $exception->getMessage());
	}
	
	public function testBatch_FailInsert() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		
		$count = 2;
		$entities = $this->_generateEntities ( $count );
		$storageClient->insertEntity ( $tableName, $entities [0] );
		
		$exception = null;
		try {
			// Start batch
			$batch = $storageClient->startBatch ();
			
			foreach ( $entities as $entity ) {
				$storageClient->insertEntity ( $tableName, $entity );
			}
			// Commit
			$batch->commit ();			
		} catch ( Exception $ex ) {		
			$exception = $ex;			
		}
		
	   $this->assertNotNull($exception, "Batch insert should fail when entity already exists.");
  	   $this->assertEquals("An error has occured while committing a batch: 0:The specified entity already exists.", $exception->getMessage());
	}
	
	/**
	 * A batch may include a single query operation that retrieves a single entity. This approach may be used to retrieve 
	 * an entity when the size of the PartitionKey and RowKey values exceed 256 characters and the entity can therefore not 
	 * be retrieved via a GET operation. Note that a query operation is not permitted within a batch that contains insert, 
	 * update, or delete operations; it must be submitted singly in the batch. 
	 *
	 */
	public function testBatch_SingleQuery() {
		$this->fail ( "API not support" );
	}
	
	/**
	 * Only one batch can be active at a time. If you start 2 batch, it will fail.
	 *
	 */
	public function testBatch_FailMultiple() {
		
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			// Start batch
			$batch1 = $storageClient->startBatch ();
			$batch2 = $storageClient->startBatch ();
			// Commit
			$batch2->commit ();
			$batch1->commit ();
		
		} catch ( Exception $ex ) {
			$exception = $ex;	
		}
	    $this->assertNotNull($exception, "Cannot start two batch at a time." );
  	    $this->assertEquals("Only one batch can be active at a time." , $exception->getMessage());
	}
	
	/**
	 * There is no operation in batch.
	 *
	 */
	public function testBatch_FailNoContent() {
		
		$storageClient = $this->_createStorageClient ();
		$exception = null;
		try {
			// Start batch
			$batch = $storageClient->startBatch ();
			// Commit
			$batch->commit ();
		
		} catch ( Exception $ex ) {
			$exception = $ex;	
		
		}
		$this->assertNotNull($exception, "No operations in a batch." );
  	    $this->assertEquals("An error has occured while committing a batch: Server encountered an internal error. Please try again after some time." , $exception->getMessage());
	}
	
	//	/**
	//	 * Test parameter $nextTableName Next table name, used for listing tables when total amount of tables is > 1000.
	//	 *
	//	 */
	//	public function testListTables_NextTable() {
	//		$storageClient = $this->_createStorageClient ();
	//		$total = 1002;
	//		try {
	//			for($i = 1; $i <= $total; $i ++) {
	//				$tableName1 = $this->generateTableName ();
	//				$storageClient->createTable ( $tableName1 );
	//			}
	//			
	//			$result = $storageClient->listTables ();
	//		} catch ( Exception $ex ) {
	//			$this->fail ( $ex->getMessage () );
	//		}
	//		$this->assertTrue ( count ( $result ) > $total );
	//	}
	

	/**
	 * Insert entity to table with some 'null' field is ok.
	 *
	 */
	public function testInsertEntity_FieldsWithNullValue() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$fileds = array ('date', 'string', 'binary', 'boolean', 'double', 'int32', 'int64', 'guid' );
		foreach ( $fileds as $field ) {
			$entitiy = $this->_generateEntities ( 1 );
			$newRowKey = $entitiy->getRowKey ();
			$entitiy->setRowKey ( $newRowKey . $field );
			$class = get_class ( $entitiy );
			$prop = new ReflectionProperty ( $class, $field . 'Field' );
			$prop->setValue ( $entitiy, null );
			echo "Test " . $field . " field with null value";
			try {
				$storageClient->insertEntity ( $tableName, $entitiy );
			} catch ( Exception $e ) {
				$this->fail ( $e->getMessage () . "\n" . $field + " Field with null value is not allowed when insert it to table!" );
			}
		}
	}
	
	/**
	 * Insert entity to table with some 'null' field is ok, but get a error when try to retrieve them.
	 * 
	 * Function  public function setAzureValues($values = array(), $throwOnError = false) in 'Microsoft_WindowsAzure_Storage_TableEntity'. the last parameter
	 * shoule explored to final user.
	 *
	 */
	public function testRetrieveEntity_InsertWithNullField() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$fileds = array ('date', 'string', 'binary', 'boolean', 'double', 'int32', 'int64', 'guid' );
		foreach ( $fileds as $field ) {
			$entitiy = $this->_generateEntities ( 1 );
			$newRowKey = $entitiy->getRowKey ();
			$entitiy->setRowKey ( $newRowKey . $field );
			$class = get_class ( $entitiy );
			$prop = new ReflectionProperty ( $class, $field . 'Field' );
			$prop->setValue ( $entitiy, null );
			echo "Test " . $field . " field with null value";
			try {
				$storageClient->insertEntity ( $tableName, $entitiy );
				$storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
			} catch ( Exception $e ) {
				$this->fail ( $e->getMessage () . "\n" . $field + " Field with null value is not allowed when insert it to table!" );
			}
		}
	}
	
	public function testRetrieveEntities_Filter() {
		$entityClass = 'Test_TableEntity';
		$count = 3;
		$storageClient = $this->_createStorageClient ();
		$tableName = $this->generateTableName ();
		$storageClient->createTable ( $tableName );
		$entities = $this->_generateEntities ( $count );
		
		// Start batch
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$batch->commit ();
		
		$result = $storageClient->retrieveEntities ( $tableName, 'PartitionKey eq \'' . $entities [0]->getPartitionKey () . '\' and RowKey eq \'' . $entities [0]->getRowKey () . '\'', $entityClass );
		$this->assertEquals ( 1, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $tableName, 'PartitionKey eq \'' . $entities [0]->getPartitionKey () . '\' or RowKey eq \'' . $entities [0]->getRowKey () . '\'', $entityClass );
		$this->assertEquals ( 3, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $tableName, 'PartitionKey eq \'' . $entities [0]->getPartitionKey () . '\'', $entityClass );
		$this->assertEquals ( 3, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $tableName, 'RowKey eq \'' . $entities [0]->getRowKey () . '\'', $entityClass );
		$this->assertEquals ( 1, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $tableName, 'not (PartitionKey eq \'' . $entities [0]->getPartitionKey () . '\' and RowKey eq \'' . $entities [0]->getRowKey () . '\')', $entityClass );
		$this->assertEquals ( 2, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $tableName, '(not (PartitionKey eq \'' . $entities [0]->getPartitionKey () . '\')) and RowKey eq \'' . $entities [0]->getRowKey () . '\'', $entityClass );
		$this->assertEquals ( 0, count ( $result ) );
	}
	
	public function testRetrieveEntities_Query() {
		$entityClass = 'Test_TableEntity';
		$count = 3;
		$storageClient = $this->_createStorageClient ();
		$tableName = $this->generateTableName ();
		$storageClient->createTable ( $tableName );
		$entities = $this->_generateEntities ( $count );
		$entities [0]->int64Field = mt_rand ( 10, 10000 );
		// Start batch
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$batch->commit ();
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->where ( 'PartitionKey eq ?', $entities [0]->getPartitionKey () )->andWhere ( 'RowKey eq ?', $entities [0]->getRowKey () ), $entityClass );
		$this->assertEquals ( 1, count ( $result ) );
		$this->assertEquals ( $entities [0]->int64Field, $result [0]->int64Field );
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->wherePartitionKey ( $entities [0]->getPartitionKey () ), $entityClass );
		$this->assertEquals ( $count, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->whereRowKey ( $entities [0]->getRowKey () ), $entityClass );
		$this->assertEquals ( 1, count ( $result ) );
	}
	
	public function testRetrieveEntities_QueryTop() {
		$entityClass = 'Test_TableEntity';
		$count = 5;
		$storageClient = $this->_createStorageClient ();
		$tableName = $this->generateTableName ();
		$storageClient->createTable ( $tableName );
		$entities = $this->_generateEntities ( $count );
		
		// Start batch
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$batch->commit ();
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->top ( 3 ), $entityClass );
		$this->assertEquals ( 3, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->top ( 1 ), $entityClass );
		$this->assertEquals ( 1, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->top ( 0 ), $entityClass );
		$this->assertEquals ( 0, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->top ( 10 ) ); // dynamic entity
		$this->assertEquals ( $count, count ( $result ) );
	}
	
	public function testRetrieveEntities_QueryOrderby() {
		$count = 5;
		$storageClient = $this->_createStorageClient ();
		$tableName = $this->generateTableName ();
		$storageClient->createTable ( $tableName );
		$entities = $this->_generateEntities ( $count );
		
		// Start batch
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$entity->int64Field = mt_rand ( 10, 10000 );
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$batch->commit ();
		$exception = null;
		try {
			$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->orderBy ( 'int64Field', 'asc' ), 'Test_TableEntity' );
			$this->assertEquals ( $count, count ( $result ) );
		} catch ( Exception $ex ) {
			$exception = $ex;			
		}
		$this->assertNotNull($exception, "Does not support order by.");
  		$this->assertEquals("The requested operation is not implemented on the specified resource.", $exception->getMessage());
	}
	
	public function testRetrieveEntityById() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 3;
		$entities = $this->_generateEntities ( $count );
		
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$entity->int32Field = mt_rand ( 10, 10000 );
			$entity->stringField = $this->_randomString ( 10 );
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$batch->commit ();
		$result = $storageClient->retrieveEntityById ( $tableName, $entities [0]->getPartitionKey (), $entities [0]->getRowKey (), 'Test_TableEntity' );
		$this->assertEquals ( $entities [0]->int32Field, $result->int32Field );
		$this->assertEquals ( $entities [0]->stringField, $result->stringField );
	
	}
	
	public function testRetrieveEntityById_NotExists() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$count = 2;
		$entities = $this->_generateEntities ( $count );
		
		$batch = $storageClient->startBatch ();
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$batch->commit ();
		$exception = null;
		try {
			$storageClient->retrieveEntityById ( $tableName, $entities [0]->getPartitionKey () . "XXX", $entities [0]->getRowKey (), 'Test_TableEntity' );
		} catch ( Exception $ex ) {
			$exception = $ex;		
		}		  
  		$this->assertNotNull($exception,"No entity with specified key");
  	    $this->assertEquals("The specified resource does not exist.", $exception->getMessage());
		
  	    $exception = null;
		try {
			$storageClient->retrieveEntityById ( "nosuchtableentity", $entities [0]->getPartitionKey () . "XXX", $entities [0]->getRowKey (), 'Test_TableEntity' );
		} catch ( Exception $ex ) {
			$exception = $ex;
			$this->assertEquals ( "The table specified does not exist.", $ex->getMessage () );		
		}
		$this->assertNotNull($exception,"Table does not exist");
  	    $this->assertEquals( "The table specified does not exist.", $exception->getMessage());
	}
	
	public function testRetrieveEntities_ComplexQuery() {
		
		$storageClient = $this->_createStorageClient ();
		$tableName = $this->generateTableName ();
		$storageClient->createTable ( $tableName );
		$count = 3;
		$batch = $storageClient->startBatch ();
		for($i = 1; $i <= $count; $i ++) {
			$dynamicEntity = new Microsoft_WindowsAzure_Storage_DynamicTableEntity ( "part_key", "row_key" . $i );
			$dynamicEntity->setAzureProperty ( "fieldA", $i, "Edm.Int32" );
			$dynamicEntity->setAzureProperty ( "fieldB", $this->_randomString ( 15 ), "Edm.String" );
			$storageClient->insertEntity ( $tableName, $dynamicEntity );
		}
		$batch->commit ();
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->orWhere ( 'fieldA eq 1' )->orWhere ( 'fieldA eq 2' ) );
		$this->assertEquals ( 2, count ( $result ) );
		
		// bug , does not support int
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->where ( 'fieldA eq ? or fieldA eq ?', array (1, 2 ) ) );
		$this->assertEquals ( 2, count ( $result ) );
	
	}
	
	/**
	 * Query entities by datetime field. The 
	 *
	 */
	public function testQueryByDatetimeField() {
		$ISO = "Y-m-d\\TH:i:s";
		$entityClass = 'Test_TableEntity';
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		try {
			$batch = $storageClient->startBatch ();
			
			$count = 20;
			
			$entities = $this->_generateEntities ( $count );
			for($i = 1; $i <= count ( $entities ); $i ++) {
				$entity = $entities [$i - 1];
				$entity->int32Field = $i;
				$nextDay = mktime ( 0, 0, 0, 01, $i, date ( "Y" ) );
				echo date ( $ISO, $nextDay );
				$entity->dateField = date ( $ISO, $nextDay );
				$storageClient->insertEntity ( $tableName, $entity );
			}
			$batch->commit ();
			$result = $storageClient->retrieveEntities ( $tableName, "int32Field le 10", $entityClass );
			$this->assertEquals ( 10, count ( $result ) );
			
			//use datatime as prefix
			$result = $storageClient->retrieveEntities ( $tableName, "dateField le " . "datetime'" . date ( $ISO ) . "'", $entityClass );
			$this->assertEquals ( $count, count ( $result ) );
			
			//use datatime as prefix
			$result = $storageClient->retrieveEntities ( $tableName, "dateField ge " . "datetime'" . date ( $ISO ) . "'", $entityClass );
			$this->assertEquals ( 0, count ( $result ) );
			
			//use datatime as prefix
			$date_condition = mktime ( 0, 0, 0, 01, 10, date ( "Y" ) );
			$result = $storageClient->retrieveEntities ( $tableName, "dateField le " . "datetime'" . date ( $ISO, $date_condition ) . "'", $entityClass );
			$this->assertEquals ( 10, count ( $result ) );
			
		//			$result = $storageClient->retrieveEntities ( $tableName, "int32Field le 10", $entityClass );
		//			$this->assertEquals ( 10, count ( $result ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Query entities by int field(include Edm.int32/Edm.int64)
	 *
	 */
	public function testQueryByIntField() {
		$entityClass = 'Test_TableEntity';
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		try {
			$batch = $storageClient->startBatch ();
			
			$count = 20;
			$entities = $this->_generateEntities ( $count );
			for($i = 1; $i <= count ( $entities ); $i ++) {
				$entity = $entities [$i - 1];
				$entity->int32Field = $i;
				$storageClient->insertEntity ( $tableName, $entity );
			}
			$batch->commit ();
			$result = $storageClient->retrieveEntities ( $tableName, "int32Field le 10", $entityClass );
			$this->assertEquals ( 10, count ( $result ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Query entities by string field
	 *
	 */
	public function testQueryByStringField() {
		$entityClass = 'Test_TableEntity';
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		try {
			$batch = $storageClient->startBatch ();
			
			$count = 20;
			$entities = $this->_generateEntities ( $count );
			for($i = 1; $i <= count ( $entities ); $i ++) {
				$entity = $entities [$i - 1];
				$entity->stringField = "A" . $i;
				
				if ($i > 10) {
					$entity->stringField = "B" . $i;
				}
				
				$storageClient->insertEntity ( $tableName, $entity );
			}
			$batch->commit ();
			$result = $storageClient->retrieveEntities ( $tableName, "stringField lt " . "'" . "B" . "'", $entityClass );
			$this->assertEquals ( 10, count ( $result ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Query entities by string field
	 *
	 */
	public function testQueryByBooleanField() {
		$entityClass = 'Test_TableEntity';
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		try {
			$batch = $storageClient->startBatch ();
			
			$count = 20;
			$entities = $this->_generateEntities ( $count );
			for($i = 1; $i <= count ( $entities ); $i ++) {
				$entity = $entities [$i - 1];
				
				if ($i > 10) {
					$entity->booleanField = true;
				} else {
					$entity->booleanField = true;
				}
				$storageClient->insertEntity ( $tableName, $entity );
			}
			$batch->commit ();
			$result = $storageClient->retrieveEntities ( $tableName, "booleanField eq " . 'true', $entityClass );
			$this->assertEquals ( 20, count ( $result ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Insert dynamic entity
	 *
	 */
	public function testInsertEntity_DynamicEntity() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		
		$dynamic = array ('name' => 'sex', 'value' => 'brith', 'type' => "Edm.String" );
		$entity = $this->_generageDynamicEntities ( $dynamic );
		
		$storageClient->insertEntity ( $tableName, $entity );
		$result = $storageClient->retrieveEntities ( $tableName );
		$one = $result [0];
		$dynamicProperty = $one->getAzureProperty ( $dynamic ['name'] );
		$this->assertSame ( $dynamic ['value'], $dynamicProperty );
	}
	
	public function testInsertEntity_BinayField() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entitiy = $this->_generateEntities ( 1 );
		$binary = base64_encode ( "I love youlllllllllllllllllllllll" );
		$entitiy->binaryField = $binary;
		try {
			$storageClient->insertEntity ( $tableName, $entitiy );
			$one = array_pop ( $storageClient->retrieveEntities ( $tableName ) );
			$this->assertEquals ( $binary, $one->binaryField );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Edm.binary support value encoded by Base64
	 *
	 */
	public function testInsertEntity_BinayFieldNotBase64() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entitiy = $this->_generateEntities ( 1 );
		$binary = "I love youlllllllllllllllllllllll";
		$entitiy->binaryField = $binary;
		$exception = null;
		try {
			$storageClient->insertEntity ( $tableName, $entitiy );
		} catch ( Exception $e ) {
			$exception = $e;			
		}
		$this->assertNotNull($exception, "Invalid entity field");
	}
	
	/**
	 * The field name can't be started with a $.
	 *
	 */
	public function testInsertEntity_FieldNameWithInvalidPrefix() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		$entitiy = new Simple_TableEntity ( 'aaa', 'bbb' );
		$binary = "I love youlllllllllllllllllllllll";
		$entitiy->binaryField = $binary;
		$exception = null;
		try {
			$storageClient->insertEntity ( $tableName, $entitiy );
		} catch ( Exception $e ) {
			$exception = $e;				
		}
		$this->assertNotNull($exception, "Invalid entity field");
	}
	
	public function testUpdateDynamicEntity() {
		$dynamicEntity = new Microsoft_WindowsAzure_Storage_DynamicTableEntity ( "part", "row" );
		$dynamicEntity->setAzureProperty ( "fieldA", "fieldA_value", "Edm.String" );
		$dynamicEntity->setAzureProperty ( "fieldB", "fieldB_value", "Edm.String" );
		
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		
		try {
			$storageClient->insertEntity ( $tableName, $dynamicEntity );
			
			$dynamicEntity = new Microsoft_WindowsAzure_Storage_DynamicTableEntity ( "part", "row" );
			$dynamicEntity->setAzureProperty ( "fieldB", "fieldB_valueUpdate", "Edm.String" );
			$dynamicEntity->setAzureProperty ( "fieldC", "fieldB_value", "Edm.String" );
			
			$storageClient->updateEntity ( $tableName, $dynamicEntity );
			$result = $storageClient->retrieveEntities ( $tableName );
			$one = $result [0];
			
			$this->assertEquals ( null, $one->getAzureProperty ( "fieldA" ) ); // FieldA is not exist any more.
			$this->assertEquals ( $dynamicEntity->getAzureProperty ( "fieldB" ), $one->getAzureProperty ( "fieldB" ) );
			$this->assertEquals ( $dynamicEntity->getAzureProperty ( "fieldC" ), $one->getAzureProperty ( "fieldC" ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testMergeDynamicEntity() {
		$dynamicEntity = new Microsoft_WindowsAzure_Storage_DynamicTableEntity ( "part", "row" );
		$dynamicEntity->setAzureProperty ( "fieldA", "fieldA_value", "Edm.String" );
		$dynamicEntity->setAzureProperty ( "fieldB", "fieldB_value", "Edm.String" );
		
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		
		try {
			$storageClient->insertEntity ( $tableName, $dynamicEntity );
			
			$dynamicEntity = new Microsoft_WindowsAzure_Storage_DynamicTableEntity ( "part", "row" );
			$dynamicEntity->setAzureProperty ( "fieldB", "fieldB_valueUpdate", "Edm.String" );
			$dynamicEntity->setAzureProperty ( "fieldC", "fieldB_value", "Edm.String" );
			
			$storageClient->mergeEntity ( $tableName, $dynamicEntity );
			$result = $storageClient->retrieveEntities ( $tableName );
			$one = $result [0];
			//Use merge, A,B,C field are all exist.
			$this->assertEquals ( "fieldA_value", $one->getAzureProperty ( "fieldA" ) );
			$this->assertEquals ( $dynamicEntity->getAzureProperty ( "fieldB" ), $one->getAzureProperty ( "fieldB" ) );
			$this->assertEquals ( $dynamicEntity->getAzureProperty ( "fieldC" ), $one->getAzureProperty ( "fieldC" ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testMergeStaticEntity() {
		$entity = $this->_generateEntities ( 1 );
		$entity->stringField = null;
		$entity->binaryField = null;
		
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createTable ( $tableName );
		
		try {
			$storageClient->insertEntity ( $tableName, $entity );
			
			$entity->stringField = "merge string value";
			$entity->binaryField = base64_encode ( "I love you!" );
			
			$storageClient->mergeEntity ( $tableName, $entity );
			$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
			$one = $result [0];
			//merged field
			$this->assertEquals ( $entity->stringField, $one->stringField );
			$this->assertEquals ( $entity->binaryField, $one->binaryField );
			// not merged field
			$this->assertEquals ( $entity->int32Field, $one->int32Field );
			$this->assertEquals ( $entity->int64Field, $one->int64Field );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	protected function _generageDynamicEntities($dynamicvalue) {
		$dynamicEntiy = new Microsoft_WindowsAzure_Storage_DynamicTableEntity ( self::$partitionKeyPrefix, self::$rowKeyPrefix );
		if ($dynamicvalue != null) {
			$dynamicEntiy->setAzureProperty ( $dynamicvalue ['name'], $dynamicvalue ['value'], $dynamicvalue ['type'] );
		}
		return $dynamicEntiy;
	}
	
	protected function _gmtTime() {
		return new DateTime ( "now", new DateTimeZone ( 'GMT' ) );
	}
	
	protected function _gmtTimeFormat() {
		$dateTime = new DateTime ( "now", new DateTimeZone ( 'GMT' ) );
		return $dateTime->format ( "Y-m-d H:i:s" );
	}
	
	/**
	 * Generate ISO 8601 compliant date string in UTC time zone
	 * 
	 * @return string
	 */
	protected function _isoDate() {
		$tz = @date_default_timezone_get ();
		@date_default_timezone_set ( 'UTC' );
		$returnValue = str_replace ( '+00:00', 'Z', @date ( 'c' ) );
		@date_default_timezone_set ( $tz );
		return $returnValue;
	}

}

class Simple_TableEntity extends Microsoft_WindowsAzure_Storage_TableEntity {
	/**
	 * 
	 * @azure $binaryField Edm.Binary
	 */
	public $binaryField = null;
}

// Call Microsoft_WindowsAzure_TableTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_WindowsAzure_TableTest::main") {
	Microsoft_WindowsAzure_TableTest::main ();
}

?>