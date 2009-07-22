<?php
/**
 * Copyright (c) 2009, RealDolmen
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of RealDolmen nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY RealDolmen ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL RealDolmen BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage UnitTests
 * @version    $Id: BlobStorageTest.php 14561 2009-05-07 08:05:12Z unknown $
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Microsoft_Azure_TableStorageTest::main');
}

/**
 * Test helpers
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';
require_once dirname(__FILE__) . '/../../TestConfiguration.php';
require_once 'PHPUnit/Framework/TestCase.php';

/** Microsoft_Azure_Storage_Table */
require_once 'Microsoft/Azure/Storage/Table.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage UnitTests
 * @version    $Id: BlobStorageTest.php 14561 2009-05-07 08:05:12Z unknown $
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_Azure_TableStorageTest extends PHPUnit_Framework_TestCase
{
    static $path;
    
    public function __construct()
    {
        self::$path = dirname(__FILE__).'/_files/';
    }
    
    public static function main()
    {
        if (TESTS_TABLE_RUNTESTS)
        {
            $suite  = new PHPUnit_Framework_TestSuite("Microsoft_Azure_TableStorageTest");
            $result = PHPUnit_TextUI_TestRunner::run($suite);
        }
    }
    
    /**
     * Test setup
     */
    protected function setUp()
    {
    }
    
    /**
     * Test teardown
     */
    protected function tearDown()
    {
        $storageClient = $this->createStorageInstance();
        for ($i = 1; $i <= self::$uniqId; $i++)
        {
            try { $storageClient->deleteTable(TESTS_TABLE_TABLENAME_PREFIX . $i); } catch (Exception $e) { }
        }
    }
    
    protected function createStorageInstance()
    {
        $storageClient = null;
        if (TESTS_TABLE_RUNONPROD)
        {
            $storageClient = new Microsoft_Azure_Storage_Table(TESTS_TABLE_HOST_PROD, TESTS_STORAGE_ACCOUNT_PROD, TESTS_STORAGE_KEY_PROD, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        }
        else
        {
            $storageClient = new Microsoft_Azure_Storage_Table(TESTS_TABLE_HOST_DEV, TESTS_STORAGE_ACCOUNT_DEV, TESTS_STORAGE_KEY_DEV, true, Microsoft_Azure_RetryPolicy::retryN(10, 250));
            $storageClient->setOdbcSettings(TESTS_TABLE_DEVCNSTRING, TESTS_TABLE_DEVCNUSER, TESTS_TABLE_DEVCNPASS);
        }
        
        if (TESTS_STORAGE_USEPROXY)
        {
            $storageClient->setProxy(TESTS_STORAGE_USEPROXY, TESTS_STORAGE_PROXY, TESTS_STORAGE_PROXY_PORT, TESTS_STORAGE_PROXY_CREDENTIALS);
        }

        return $storageClient;
    }
    
    protected static $uniqId = 0;
    
    protected function generateName()
    {
        self::$uniqId++;
        return TESTS_TABLE_TABLENAME_PREFIX . self::$uniqId;
    }
    
    /**
     * Test generate development table
     */
    public function testGenerateDevelopmentTable()
    {  
        if (TESTS_TABLE_RUNTESTS && !TESTS_TABLE_RUNONPROD)
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->generateDevelopmentTable('TSTest_TestEntity', $tableName);
            
            // NOTE 1: ODBC connection credentails should be specified.
            // NOTE 2: Table storage must be RESTARTED after generating tables. 
        }
    }
    
    /**
     * Test create table
     */
    public function testCreateTable()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            
            $result = $storageClient->createTable($tableName);
            $this->assertEquals($tableName, $result->Name);
            
            $result = $storageClient->listTables();
            $this->assertEquals(1, count($result));
            $this->assertEquals($tableName, $result[0]->Name);
        }
    }
    
    /**
     * Test list tables
     */
    public function testListTables()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName1 = $this->generateName();
            $tableName2 = $this->generateName();
            $storageClient = $this->createStorageInstance();
            
            $storageClient->createTable($tableName1);
            $storageClient->createTable($tableName2);

            $result = $storageClient->listTables();
            $this->assertEquals(2, count($result));
            $this->assertEquals($tableName1, $result[0]->Name);
            $this->assertEquals($tableName2, $result[1]->Name);
        }
    }
    
    /**
     * Test delete table
     */
    public function testDeleteTable()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            
            $storageClient->createTable($tableName);
            $storageClient->deleteTable($tableName);
            
            $result = $storageClient->listTables();
            $this->assertEquals(0, count($result));
        }
    }
    
    /**
     * Test insert entity
     */
    public function testInsertEntity()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $result = $storageClient->insertEntity($tableName, $entity);

            $this->assertNotEquals('0001-01-01T00:00:00', $result->getTimestamp());
            $this->assertNotEquals('', $result->getEtag());
            $this->assertEquals($entity, $result);
        }
    }
    
    /**
     * Test delete entity, not taking etag into account
     */
    public function testDeleteEntity_NoEtag()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $result = $storageClient->insertEntity($tableName, $entity);
            
            $this->assertEquals($entity, $result);
            
            $storageClient->deleteEntity($tableName, $entity);
        }
    }
    
    /**
     * Test delete entity, taking etag into account
     */
    public function testDeleteEntity_Etag()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $result = $storageClient->insertEntity($tableName, $entity);

            $this->assertEquals($entity, $result);

            // Set "old" etag
            $entity->setEtag('W/"datetime\'2009-05-27T12%3A15%3A15.3321531Z\'"');
            
            $exceptionThrown = false;
            try {
                $storageClient->deleteEntity($tableName, $entity, true);
            } catch (Exception $ex) {
                $exceptionThrown = true;
            }
            $this->assertTrue($exceptionThrown);
        }
    }
    
    /**
     * Test retrieve entity by id
     */
    public function testRetrieveEntityById()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $storageClient->insertEntity($tableName, $entity);
            
            $result = $storageClient->retrieveEntityById($tableName, $entity->getPartitionKey(), $entity->getRowKey(), 'TSTest_TestEntity');
            $this->assertEquals($entity, $result);
        }
    }
    
    /**
     * Test retrieve entity by id, DynamicTableEntity
     */
    public function testRetrieveEntityById_DynamicTableEntity()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $storageClient->insertEntity($tableName, $entity);
            
            $result = $storageClient->retrieveEntityById($tableName, $entity->getPartitionKey(), $entity->getRowKey());
            $this->assertEquals($entity->FullName, $result->Name);
            $this->assertType('Microsoft_Azure_Storage_DynamicTableEntity', $result);
        }
    }
    
    /**
     * Test update entity, not taking etag into account
     */
    public function testUpdateEntity_NoEtag()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $storageClient->insertEntity($tableName, $entity);
            $entity->Age = 0;
            
            $result = $storageClient->updateEntity($tableName, $entity);

            $this->assertNotEquals('0001-01-01T00:00:00', $result->getTimestamp());
            $this->assertNotEquals('', $result->getEtag());
            $this->assertEquals(0, $result->Age);
            $this->assertEquals($entity, $result);
        }
    }
    
    /**
     * Test update entity, taking etag into account
     */
    public function testUpdateEntity_Etag()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $storageClient->insertEntity($tableName, $entity);
            $entity->Age = 0;
            
            // Set "old" etag
            $entity->setEtag('W/"datetime\'2009-05-27T12%3A15%3A15.3321531Z\'"');
            
            $exceptionThrown = false;
            try {
                $storageClient->updateEntity($tableName, $entity, true);
            } catch (Exception $ex) {
                $exceptionThrown = true;
            }
            $this->assertTrue($exceptionThrown);
        }
    }
    
    /**
     * Test merge entity, not taking etag into account
     */
    public function testMergeEntity_NoEtag()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $storageClient->insertEntity($tableName, $entity);
            
            $dynamicEntity = new Microsoft_Azure_Storage_DynamicTableEntity($entity->getPartitionKey(), $entity->getRowKey());
            $dynamicEntity->Myproperty = 10;
            $dynamicEntity->Otherproperty = "Test";
            $dynamicEntity->Age = 0;
            
            $storageClient->mergeEntity($tableName, $dynamicEntity);
            
            $result = $storageClient->retrieveEntityById($tableName, $entity->getPartitionKey(), $entity->getRowKey());

            $this->assertNotEquals('0001-01-01T00:00:00', $result->getTimestamp());
            $this->assertNotEquals('', $result->getEtag());
            $this->assertEquals(0, $result->Age);
            $this->assertEquals($entity->FullName, $result->Name);
            $this->assertEquals($dynamicEntity->Myproperty, $result->Myproperty);
            $this->assertEquals($dynamicEntity->Otherproperty, $result->Otherproperty);
        }
    }
    
    /**
     * Test merge entity, taking etag into account
     */
    public function testMergeEntity_Etag()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(1);
            $entity = $entities[0];
            
            $storageClient->insertEntity($tableName, $entity);
            
            $dynamicEntity = new Microsoft_Azure_Storage_DynamicTableEntity($entity->getPartitionKey(), $entity->getRowKey());
            $dynamicEntity->Myproperty = 10;
            $dynamicEntity->Otherproperty = "Test";
            $dynamicEntity->Age = 0;
            
            // Set "old" etag
            $entity->setEtag('W/"datetime\'2009-05-27T12%3A15%3A15.3321531Z\'"');
            
            $exceptionThrown = false;
            try {
                $storageClient->mergeEntity($tableName, $dynamicEntity, true);
            } catch (Exception $ex) {
                $exceptionThrown = true;
            }
            $this->assertTrue($exceptionThrown);
        }
    }
    
    /**
     * Test retrieve entities, all
     */
    public function testRetrieveEntities_All()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(20);
            foreach ($entities as $entity)
            {
                $storageClient->insertEntity($tableName, $entity);
            }
            
            $result = $storageClient->retrieveEntities($tableName, 'TSTest_TestEntity');
            $this->assertEquals(20, count($result));
        }
    }
    
    /**
     * Test retrieve entities, all, DynamicTableEntity
     */
    public function testRetrieveEntities_All_DynamicTableEntity()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(20);
            foreach ($entities as $entity)
            {
                $storageClient->insertEntity($tableName, $entity);
            }
            
            $result = $storageClient->retrieveEntities($tableName);
            $this->assertEquals(20, count($result));
            
            foreach ($result as $item)
            {
                $this->assertType('Microsoft_Azure_Storage_DynamicTableEntity', $item);
            }
        }
    }
    
    /**
     * Test retrieve entities, filtered
     */
    public function testRetrieveEntities_Filtered()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(5);
            foreach ($entities as $entity)
            {
                $storageClient->insertEntity($tableName, $entity);
            }
            
            $result = $storageClient->retrieveEntities($tableName, 'PartitionKey eq \'' . $entities[0]->getPartitionKey() . '\' and RowKey eq \'' . $entities[0]->getRowKey() . '\'', 'TSTest_TestEntity');
            $this->assertEquals(1, count($result));
        }
    }
    
    /**
     * Test retrieve entities, fluent interface
     */
    public function testRetrieveEntities_Fluent()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(10);
            foreach ($entities as $entity)
            {
                $storageClient->insertEntity($tableName, $entity);
            }
            
            $result = $storageClient->retrieveEntities(
                $storageClient->select()
                              ->from($tableName)
                              ->where('Name eq ?', $entities[0]->FullName)
                              ->andWhere('RowKey eq ?', $entities[0]->getRowKey()),
                'TSTest_TestEntity'
            );
            
            $this->assertEquals(1, count($result));
            $this->assertEquals($entities[0], $result[0]);
        }
    }
    
    /**
     * Test batch commit, success
     */
    public function testBatchCommit_Success()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(10);
            
            // Start batch
            $batch = $storageClient->startBatch();
            $this->assertType('Microsoft_Azure_Storage_Batch', $batch);
            
            // Insert entities in batch
            foreach ($entities as $entity)
            {
                $storageClient->insertEntity($tableName, $entity);
            }
            
            // Commit
            $batch->commit();
            
            // Verify
            $result = $storageClient->retrieveEntities($tableName);
            $this->assertEquals(10, count($result));
        }
    }
    
    /**
     * Test batch rollback, success
     */
    public function testBatchRollback_Success()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(10);
            
            // Start batch
            $batch = $storageClient->startBatch();
            $this->assertType('Microsoft_Azure_Storage_Batch', $batch);
            
            // Insert entities in batch
            foreach ($entities as $entity)
            {
                $storageClient->insertEntity($tableName, $entity);
            }
            
            // Rollback
            $batch->rollback();
            
            // Verify
            $result = $storageClient->retrieveEntities($tableName);
            $this->assertEquals(0, count($result));
        }
    }
    
    /**
     * Test batch commit, fail updates
     */
    public function testBatchCommit_FailUpdates()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(10);
            foreach ($entities as $entity)
            {
                $storageClient->insertEntity($tableName, $entity);
            }
            
            // Make some entity updates with "old" etags
            $entities[0]->Age = 0;
            $entities[0]->setEtag('W/"datetime\'2009-05-27T12%3A15%3A15.3321531Z\'"');
            $entities[1]->Age = 0;
            $entities[1]->setEtag('W/"datetime\'2009-05-27T12%3A15%3A15.3321531Z\'"');
            $entities[2]->Age = 0;
            
            // Start batch
            $batch = $storageClient->startBatch();
            $this->assertType('Microsoft_Azure_Storage_Batch', $batch);
            
            // Update entities in batch
            $storageClient->updateEntity($tableName, $entities[0], true);
            $storageClient->updateEntity($tableName, $entities[1], true);
            $storageClient->updateEntity($tableName, $entities[2], true);
            
            // Commit
            $exceptionThrown = false;
            try {
                $batch->commit();
            } catch (Exception $ex) {
                $exceptionThrown = true;
            }
            $this->assertTrue($exceptionThrown);
        }
    }
    
    /**
     * Test batch commit, fail partition
     */
    public function testBatchCommit_FailPartition()
    {
        if (TESTS_TABLE_RUNTESTS && TESTS_TABLE_RUNONPROD) 
        {
            $tableName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createTable($tableName);
            
            $entities = $this->_generateEntities(10);
            
            // Start batch
            $batch = $storageClient->startBatch();
            $this->assertType('Microsoft_Azure_Storage_Batch', $batch);
            
            // Insert entities in batch
            foreach ($entities as $entity)
            {
                $entity->setPartitionKey('partition' . rand(1, 9));
                $storageClient->insertEntity($tableName, $entity);
            }
            
            // Commit
            $exceptionThrown = false;
            try {
                $batch->commit();
            } catch (Exception $ex) {
                $exceptionThrown = true;
            }
            $this->assertTrue($exceptionThrown);
            
            // Verify
            $result = $storageClient->retrieveEntities($tableName);
            $this->assertEquals(0, count($result));
        }
    }
    
    /**
     * Generate entities
     * 
     * @param int 		$amount Number of entities to generate
     * @return array 			Array of TSTest_TestEntity
     */
    protected function _generateEntities($amount = 1)
    {
        $returnValue = array();
        
        for ($i = 0; $i < $amount; $i++)
        {
            $entity = new TSTest_TestEntity('partition1', 'row' . ($i + 1));
            $entity->FullName = md5(uniqid(rand(), true));
            $entity->Age      = rand(1, 130);
            $entity->Visible  = rand(1,2) == 1;
            
            $returnValue[] = $entity;
        }
        
        return $returnValue;
    }
}

/**
 * Test Microsoft_Azure_Storage_TableEntity class
 */
class TSTest_TestEntity extends Microsoft_Azure_Storage_TableEntity
{
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

// Call Microsoft_Azure_TableStorageTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_TableStorageTest::main") {
    Microsoft_Azure_TableStorageTest::main();
}
