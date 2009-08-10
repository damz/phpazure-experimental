<?php

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_LocalTableTest::main' );
}

require_once 'PHPUnit/Framework.php';

//require_once 'TableTest.php';


require_once 'Microsoft/Azure/Storage/Table.php';
require_once 'TestTableEntity.php';

class Microsoft_Azure_LocalTableTest extends PHPUnit_Framework_TestCase {
	protected static $tablePrefix = "phptabletest";
	
	protected static $partitionKeyPrefix = "partition";
	protected static $rowKeyPrefix = "row";
	
	protected static $uniqId = 0;
	
	protected $_tempTables = array ();
	
	protected $_tempEntities = array ();
	
	protected function generateTableName() {
		self::$uniqId ++;
		$name = self::$tablePrefix . self::$uniqId;
		$this->_tempTables [] = $name;
		return $name;
	}
	public function __construct() {
		require_once 'LocalTestConfiguration.php';
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
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient();
		$result = $storageClient->retrieveEntities ( $tableName);
//		$one = $result[0];
		
	    if (is_array($result) && count($result) > 0){
	    	
	    	foreach ($result as $entity)
	    	try{
	    		$storageClient->deleteEntity ( $tableName, $entity );
	    	}catch (Exception $e){
	    		
	    	}
	    }
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_LocalTableTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	function _createStorageClient() {
		return new Microsoft_Azure_Storage_Table ( TABLE_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN ( 10, 250 ) );
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
	
	function _tableExists($result, $tableName) {
		foreach ( $result as $table )
			if ($table->Name == $tableName) {
				return true;
			}
		return false;
	}

	
	function _randomString($length = 1) {
		$char = array ();
		for($i = 0; $i < $length; $i ++)
			$char [] = chr ( rand ( 65, 90 ) );
		return implode ( "", $char );
	}
	
	function _randomFloat($min, $max) {
		return ($min + lcg_value () * (abs ( $max - $min )));
	}
	
	/*
	 * Test generate table in development storage 
	 */
	public function testGenerateTable() {
		$tableName = "TestTable"; //$this -> generateTableName();
		$storageClient = $this->_createStorageClient ();
		try {
			$storageClient->setOdbcSettings ( "local", "Administrator", "" );
			$result = $storageClient->generateDevelopmentTable ( "Test_TableEntity", $tableName );
			
			$result = $storageClient->listTables ();
	       // $this->asserTrue($result->Name, $tableName);
		} catch ( Exception $e ) {
			$this->fail ( $ex->getMessage () );
		}
		
		$this -> assertTrue ( count($result)>0 );
		$this -> assertTrue ($this -> _tableExists($result, $tableName));
	}
	
	/*
		 * Test generate table in development storage 
		 */
	public function tttestGenerateTable_Conflict() {
		$tableName = $this->generateTableName ();
		$storageClient = $this->_createStorageClient ();
		
		$exception = null;
		
		try {
			$storageClient->setOdbcSettings ( "local", "Administrator", "" );
			$result = $storageClient->generateDevelopmentTable ( "Simple_TableEntity", $tableName );
			$result = $storageClient->generateDevelopmentTable ( "Simple_TableEntity", $tableName );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
	    $this->assertNotNull($exception, "table alread exists");
  		$this->assertEquals("The table specified already exists.", $exception->getMessage());
	}
	
	/*
		 * Test generate table in development storage with an illegal name
		 */
	public function ttestGenerateTable_Illegal() {
		$tableName = "fs&d2_fa";
		$storageClient = $this->_createStorageClient ();
		
		$exception = null;
		try {
			$storageClient->setOdbcSettings ( "local", "Administrator", "" );
			$result = $storageClient->generateDevelopmentTable ( "Simple_TableEntity", $tableName );
	
		} catch ( Exception $e ) {
			$exception = $e;
		}
		
		$this->assertNotNull ($exception, "Illegal table name");
		$this->assertEqauls ( "One of the request is out of range", $exception->getMessage () );
	}
	
	/**
	 * Test list tables
	 */
	public function testListTables() {
		$storageClient = $this->_createStorageClient ();
		$tableName1 = $this->generateTableName ();
		$tableName2 = $this->generateTableName ();
		try {
			$storageClient->setOdbcSettings ( "local", "Administrator", "" );
			$storageClient->generateDevelopmentTable ( "Simple_TableEntity", $tableName1 );
			$storageClient->generateDevelopmentTable ( "Simple_TableEntity", $tableName2 );
			$result = $storageClient->listTables ();
			
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
		$this->assertTrue ( count ( $result ) > 2 );
		
		//Restart the development storage. then the new table can be found.
		$this->assertTrue ( $this->_tableExists ( $result, $tableName1 ) );
		$this->assertTrue ( $this->_tableExists ( $result, $tableName2 ) );
	}
	
	/**
	 * Test delete table, The local Table service does not support dynamic deletion of tables. 
	 * 
	 */
	public function testDeleteTable() {
		//suppose that the table "deleteTable" is exist.
		$tableName = "deleteTable";
		$storageClient = $this->_createStorageClient ();
		
		try {
			$storageClient->deleteTable ( $tableName );
			
			$result = $storageClient->listTables ();
			$this->assertFalse ( $this->_tableExists ( $result, $tableName ) );
		} catch ( Exception $ex ) {
			$this->fail ( $ex->getMessage () );
		}
	}
	
	public function testDeleteTable_NotExists() {
		//Suppose the table "notexisttable" does not exist.
		$tableName = "notexisttable";
		$storageClient = $this->_createStorageClient ();
		
		$exception = null;
		try {
			$storageClient->deleteTable ( $tableName );
		} catch ( Exception $ex ) {
	         $exception = $ex;	
		}
		
		$this->assertNotNull($exception, "Table does not exist.");
		$this->assertEquals ( "The specified resource does not exist.", $exception->getMessage () );
	}
	
public function testInsertEntity_DateTimeFieldInGMTFormat() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$entity = ($this->_generateEntities ( 1 ));
		$dateTime = $this->_gmtTimeFormat ();
		$entity->dateField = $dateTime;
		
		$exception = $null;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this -> assertNotNull($exception, "Time format is not supported");
	}
	
	//Test insert operation
	public function testInsertEntity() {
		
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$entity = $this->_generateEntities ( 1 );
		$storageClient->insertEntity ( $tableName, $entity );
		$result = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( 1, sizeof ( $result ) );
		
	}
	
	public function testInsertEntity_Multiple() {
		
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		$count = 3;
		$entities = $this->_generateEntities ( $count );
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		$result = $storageClient->retrieveEntities ( $tableName );
		$this->assertEquals ( $count, sizeof ( $result ) );
		
	}
	
	public function testInsertEntity_LargeStringField() {
		
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$entity = ($this->_generateEntities ( 1 ));
		$string = str_repeat ( "hello ketty", 10 );
		$entity->stringField = $string;
		$storageClient->insertEntity ( $tableName, $entity );
		$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
		$one = $result [0];
		$this->assertTrue ( $one->stringField == $string );

	}
	
	public function testInsertEntity_NullField() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$entity = ($this->_generateEntities ( 1 ));
		$entity->stringField = null;
		
		$exception = null;
		try {
			$storageClient->insertEntity ( $tableName, $entity );
		} catch ( Exception $e ) {
			$exception = $e;
		}
		
		$this->assertTrue ( get_class ( $exception ) == Mircosoft_Azure_Exception );
	}
	
	public function testInsertEntity_Int32Field() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
		$this->assertEquals ( 'Microsoft_Azure_Exception', get_class ( $e ) );
	}
	
	
	
	/**
	 * Only ISO date format is suopport.
	 *
	 */
	public function testInsertEntity_DateTimeFieldInISOFormat() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$entity = ($this->_generateEntities ( 1 ));
		$dateTime = $this->_isoDate ();
		$entity->dateField = $dateTime;
		$storageClient->insertEntity ( $tableName, $entity );
		$result = $storageClient->retrieveEntities ( $tableName, "", "Test_TableEntity" );
		$one = $result [0];
		$this->assertTrue ( $one->dateField == $dateTime );
		$storageClient->deleteEntity ( $tableName, $entity );
		
	}
	
	public function testUpdateEntity_ValidField() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
	
//	public function testBatch_Rollback() {
//		// The table LocalTestTable is exist.
//		$tableName = "LocalTestTable";
//		$storageClient = $this->_createStorageClient ();
//		
//		$count = 2;
//		$entities = $this->_generateEntities ( $count );
//		foreach ( $entities as $entity ) {
//			$storageClient->insertEntity ( $tableName, $entity );
//		}
//		
//		foreach ( $entities as $entity ) {
//			$storageClient->deleteEntity ( $tableName, $entity );
//		}
//
//		// Rollback
//		$batch->rollback ();
//		$result = $storageClient->retrieveEntities ( $tableName );
//		$this->assertEquals ( $count, sizeof ( $result ) );
//	}
	
	/**
	 * Insert entity to table with some 'null' field is ok.
	 *
	 */
	public function testInsertEntity_FieldsWithNullValue() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
	 * Insert entity to table with some 'null' field is ok, and try to retrieve them in development storage is ok.
	 *
	 */
	public function testRetrieveEntity_InsertWithNullField() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$entities = $this->_generateEntities ( $count );
		
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
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
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$entities = $this->_generateEntities ( $count );
		$entities [0]->int64Field = mt_rand ( 10, 10000 );
		
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->where ( 'PartitionKey eq ?', $entities [0]->getPartitionKey () )->andWhere ( 'RowKey eq ?', $entities [0]->getRowKey () ), $entityClass );
		$this->assertEquals ( 1, count ( $result ) );
		$this->assertEquals ( $entities [0]->int64Field, $result [0]->int64Field );
		
		/* in development storage, when carry on below query will thrown an exception.
		 * 
		 * Does not support wherePartitionKey and whereRowKey
		 */
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->wherePartitionKey ( $entities [0]->getPartitionKey () ), $entityClass );
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->where ('PartitionKey eq ?', $entities [0]->getPartitionKey () ), $entityClass );		
		$this->assertEquals ( $count, count ( $result ) );
		
		$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->whereRowKey ( $entities [0]->getRowKey () ), $entityClass );
		$this->assertEquals ( 1, count ( $result ) );
		
	}
	
	public function testRetrieveEntities_QueryTop() {
		$entityClass = 'Test_TableEntity';
		$count = 5;
		$storageClient = $this->_createStorageClient ();
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$entities = $this->_generateEntities ( $count );
		$entities [0]->int64Field = mt_rand ( 10, 10000 );
		
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
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
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$entities = $this->_generateEntities ( $count );
		
		foreach ( $entities as $entity ) {
			$entity->int64Field = mt_rand ( 10, 10000 );
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
		$exception = null;
		try {
			$result = $storageClient->retrieveEntities ( $storageClient->select ()->from ( $tableName )->orderBy ( 'int64Field', 'asc' ), 'Test_TableEntity' );
			$this->assertEquals ( $count, count ( $result ) );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this -> assertNotNull($exception);
	}
	
	public function testRetrieveEntityById() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$count = 3;
		$entities = $this->_generateEntities ( $count );
		
		foreach ( $entities as $entity ) {
			$entity->int32Field = mt_rand ( 10, 10000 );
			$entity->stringField = $this->_randomString ( 10 );
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
		$result = $storageClient->retrieveEntityById ( $tableName, $entities [0]->getPartitionKey (), $entities [0]->getRowKey (), 'Test_TableEntity' );
		$this->assertEquals ( $entities [0]->int32Field, $result->int32Field );
		$this->assertEquals ( $entities [0]->stringField, $result->stringField );

	}
	
	public function testRetrieveEntityById_NotExists() {
		// The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$count = 2;
		$exception = null;
		$entities = $this->_generateEntities ( $count );
		
		foreach ( $entities as $entity ) {
			$storageClient->insertEntity ( $tableName, $entity );
		}
		
		try {
			$storageClient->retrieveEntityById ( $tableName, $entities [0]->getPartitionKey () . "XXX", $entities [0]->getRowKey (), 'Test_TableEntity' );
			$this->fail ( "the ID does not exist." );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		
		$this->assertNotNull($exception,"No entity with specified key");
  	    $this->assertEquals("The specified resource does not exist.", $exception->getMessage());
		
		try {
			$storageClient->retrieveEntityById ( "nosuchtableentity", $entities [0]->getPartitionKey () . "XXX", $entities [0]->getRowKey (), 'Test_TableEntity' );
//			$this->fail ( "the ID does not exist." );
		} catch ( Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotNull($exception,"Table does not exist");
  	    $this->assertEquals( "The table specified does not exist.", $exception->getMessage());
		
	}
	
	/**
	 * Query entities by int field(include Edm.int32/Edm.int64)
	 *
	 */
	//	public function testQueryByIntField() {
	//		$entityClass = 'Test_TableEntity';
	//		   // The table LocalTestTable is exist.
	//		$tableName = "LocalTestTable";
	//		$storageClient = $this->_createStorageClient ();
	//		try {
	//	
	//			$entities = $this->_generateEntities ( $count );
	//			for($i = 1; $i <= count ( $entities ); $i ++) {
	//				$entity = $entities [$i - 1];
	//				$entity->int32Field = $i;
	//				$storageClient->insertEntity ( $tableName, $entity );
	//			}
	//	
	//			$result = $storageClient->retrieveEntities ( $tableName, "int32Field le 10", $entityClass );
	//			$this->assertEquals ( 10, count ( $result ) );
	//		} catch ( Exception $e ) {
	//			$this->fail ( $e->getMessage () );
	//		}
	//		
	//	for($i = 1; $i <= count ( $entities ); $i ++) {
	//				$storageClient->deleteEntity ( $tableName, $entity );
	//			}
	//	}
	

	/**
	 * Query entities by string field
	 *
	 */
	public function testQueryByStringField() {
		$entityClass = 'Test_TableEntity';
		//The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		$count = 20;
		$entities = $this->_generateEntities ( $count );
		
		try {
			for($i = 1; $i <= count ( $entities ); $i ++) {
				$entity = $entities [$i - 1];
				$entity->stringField = "A" . $i;
				
				if ($i > 10) {
					$entity->stringField = "B" . $i;
				}
				
				$storageClient->insertEntity ( $tableName, $entity );
			}
			$result = $storageClient->retrieveEntities ( $tableName, "stringField lt " . "'" . "B" . "'", $entityClass );
			$this->assertEquals ( 10, count ( $result ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Query entities by boolean field
	 *
	 */
	public function testQueryByBooleanField() {
		$entityClass = 'Test_TableEntity';
		//The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		try {
			
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
			$result = $storageClient->retrieveEntities ( $tableName, "booleanField eq " . 'true', $entityClass );
			$this->assertEquals ( 20, count ( $result ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}

	}
	
	public function testInsertEntity_BinayField() {
		//The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
		//The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
		$entitiy = $this->_generateEntities ( 1 );
		$binary = "I love youlllllllllllllllllllllll";
		$entitiy->binaryField = $binary;
		try {
			$storageClient->insertEntity ( $tableName, $entitiy );
		} catch ( Exception $e ) {
			$this->assertTrue ( $e != null );
		}
	
	}
	
	/**
	 * The field name can't be started with a $.
	 *
	 */
	public function testInsertEntity_FieldNameWithInvalidPrefix() {
		//The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
	
	
	public function testMergeStaticEntity() {
		$entity = $this->_generateEntities ( 1 );
		$entity->stringField = null;
		$entity->binaryField = null;
		
		//The table LocalTestTable is exist.
		$tableName = "LocalTestTable";
		$storageClient = $this->_createStorageClient ();
		
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
	
	/* Generate ISO 8601 compliant date string in UTC time zone
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
	
	protected function _gmtTime() {
		return new DateTime ( "now", new DateTimeZone ( 'GMT' ) );
	}
	
	protected function _gmtTimeFormat() {
		$dateTime = new DateTime ( "now", new DateTimeZone ( 'GMT' ) );
		return $dateTime->format ( "Y-m-d H:i:s" );
	}

}

class Simple_TableEntity extends Microsoft_Azure_Storage_TableEntity {
	/**
	 * @azure Name Edm.String
	 */
	public $stringField = "name";
	
	/**
	 * @azure Address Edm.String
	 */
	public $stringField1 = "address";
}

// Call Microsoft_Azure_LocalTableTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_LocalTableTest::main") {
	Microsoft_Azure_LocalBlobTest::main ();
}

?>