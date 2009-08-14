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
 * @version    $Id$
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Microsoft_Azure_BlobStorageTest::main');
}

/**
 * Test helpers
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';
require_once dirname(__FILE__) . '/../../TestConfiguration.php';
require_once 'PHPUnit/Framework/TestCase.php';

/** Microsoft_Azure_Storage_Blob */
require_once 'Microsoft/Azure/Storage/Blob.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage UnitTests
 * @version    $Id$
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_Azure_BlobStorageTest extends PHPUnit_Framework_TestCase
{
    static $path;
    
    public function __construct()
    {
        self::$path = dirname(__FILE__).'/_files/';
    }
    
    public static function main()
    {
        if (TESTS_BLOB_RUNTESTS) 
        {
            $suite  = new PHPUnit_Framework_TestSuite("Microsoft_Azure_BlobStorageTest");
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
            try { $storageClient->deleteContainer(TESTS_BLOB_CONTAINER_PREFIX . $i); } catch (Exception $e) { }
        }
        try { $storageClient->deleteContainer('$root'); } catch (Exception $e) { }
    }

    protected function createStorageInstance()
    {
        $storageClient = null;
        if (TESTS_BLOB_RUNONPROD)
        {
            $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST_PROD, TESTS_STORAGE_ACCOUNT_PROD, TESTS_STORAGE_KEY_PROD, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        }
        else
        {
            $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST_DEV, TESTS_STORAGE_ACCOUNT_DEV, TESTS_STORAGE_KEY_DEV, true, Microsoft_Azure_RetryPolicy::retryN(10, 250));
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
        return TESTS_BLOB_CONTAINER_PREFIX . self::$uniqId;
    }
    
    /**
     * Test container exists
     */
    public function testContainerExists()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName1 = $this->generateName();
            $containerName2 = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName1);
            $storageClient->createContainer($containerName2);
            
            $result = $storageClient->containerExists($containerName1);
            $this->assertTrue($result);
            
            $result = $storageClient->containerExists(md5(time()));
            $this->assertFalse($result);
        }
    }
    
    /**
     * Test blob exists
     */
    public function testBlobExists()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            $storageClient->putBlob($containerName, 'WindowsAzure1.gif', self::$path . 'WindowsAzure.gif');
            $storageClient->putBlob($containerName, 'WindowsAzure2.gif', self::$path . 'WindowsAzure.gif');
            
            $result = $storageClient->blobExists($containerName, 'WindowsAzure1.gif');
            $this->assertTrue($result);
            
            $result = $storageClient->blobExists($containerName, md5(time()));
            $this->assertFalse($result);
        }
    }

    /**
     * Test create container
     */
    public function testCreateContainer()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $result = $storageClient->createContainer($containerName);
            $this->assertEquals($containerName, $result->Name);
        }
    }
    
    /**
     * Test get container acl
     */
    public function testGetContainerAcl()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            $acl = $storageClient->getContainerAcl($containerName);
            $this->assertEquals(Microsoft_Azure_Storage_Blob::ACL_PRIVATE, $acl);        
        }
    }
    
    /**
     * Test set container acl
     */
    public function testSetContainerAcl()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            
            $storageClient->setContainerAcl($containerName, Microsoft_Azure_Storage_Blob::ACL_PUBLIC);
            $acl = $storageClient->getContainerAcl($containerName);
            
            $this->assertEquals(Microsoft_Azure_Storage_Blob::ACL_PUBLIC, $acl);
        }
    }
    
    /**
     * Test set container acl advanced
     */
    public function testSetContainerAclAdvanced()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            
            $storageClient->setContainerAcl(
                $containerName,
                Microsoft_Azure_Storage_Blob::ACL_PRIVATE,
                array(
                    new Microsoft_Azure_Storage_SignedIdentifier('ABCDEF', '2009-10-10', '2009-10-11', 'r')
                )
            );
            $acl = $storageClient->getContainerAcl($containerName, true);
            
            $this->assertEquals(1, count($acl));
        }
    }

    /**
     * Test set container metadata
     */
    public function testSetContainerMetadata()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            
            $storageClient->setContainerMetadata($containerName, array(
                'createdby' => 'PHPAzure',
            ));
            
            $metadata = $storageClient->getContainerMetadata($containerName);
            $this->assertEquals('PHPAzure', $metadata['createdby']);
        }
    }
    
    /**
     * Test list containers
     */
    public function testListContainers()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName1 = 'testlist1';
            $containerName2 = 'testlist2';
            $containerName3 = 'testlist3';
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName1);
            $storageClient->createContainer($containerName2);
            $storageClient->createContainer($containerName3);
            $result1 = $storageClient->listContainers('testlist');
            $result2 = $storageClient->listContainers('testlist', 1);
    
            // cleanup first
            $storageClient->deleteContainer($containerName1);
            $storageClient->deleteContainer($containerName2);
            $storageClient->deleteContainer($containerName3);
            
            $this->assertEquals(3, count($result1));
            $this->assertEquals($containerName2, $result1[1]->Name);
            
            $this->assertEquals(1, count($result2));
        }
    }
    
    /**
     * Test put blob
     */
    public function testPutBlob()
    {
        if (TESTS_BLOB_RUNTESTS) 
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            $result = $storageClient->putBlob($containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
    
            $this->assertEquals($containerName, $result->Container);
            $this->assertEquals('images/WindowsAzure.gif', $result->Name);
        }
    }
    
    /**
     * Test put large blob
     */
    public function testPutLargeBlob()
    {
        if (TESTS_BLOB_RUNTESTS && TESTS_BLOB_RUNLARGEBLOB) 
        {
            // Create a file > Microsoft_Azure_Storage_Blob::MAX_BLOB_SIZE
            $fileName = $this->_createLargeFile();
            
            // Execute test
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            $result = $storageClient->putLargeBlob($containerName, 'LargeFile.txt', $fileName);
    
            $this->assertEquals($containerName, $result->Container);
            $this->assertEquals('LargeFile.txt', $result->Name);
            
            // Get block list
            $blockList = $storageClient->getBlockList($containerName, 'LargeFile.txt');
            $this->assertTrue(count($blockList['CommittedBlocks']) > 0);
            
            // Remove file
            unlink($fileName);
        }
    }
    
    /**
     * Test get blob
     */
    public function testGetBlob()
    {
        if (TESTS_BLOB_RUNTESTS) 
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            $storageClient->putBlob($containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
            
            $fileName = tempnam('', 'tst');
            $storageClient->getBlob($containerName, 'images/WindowsAzure.gif', $fileName);
    
            $this->assertTrue(file_exists($fileName));
            $this->assertEquals(
                file_get_contents(self::$path . 'WindowsAzure.gif'),
                file_get_contents($fileName)
            );
            
            // Remove file
            unlink($fileName);
        }
    }
    
    /**
     * Test set blob metadata
     */
    public function testSetBlobMetadata()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            $storageClient->putBlob($containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
            
            $storageClient->setBlobMetadata($containerName, 'images/WindowsAzure.gif', array(
                'createdby' => 'PHPAzure',
            ));
            
            $metadata = $storageClient->getBlobMetadata($containerName, 'images/WindowsAzure.gif');
            $this->assertEquals('PHPAzure', $metadata['createdby']);
        }
    }
    
    /**
     * Test delete blob
     */
    public function testDeleteBlob()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            
            $storageClient->putBlob($containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
            $storageClient->deleteBlob($containerName, 'images/WindowsAzure.gif');
            
            $result = $storageClient->listBlobs($containerName);
            $this->assertEquals(0, count($result));
        }
    }
    
    /**
     * Test list blobs
     */
    public function testListBlobs()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            
            $storageClient->putBlob($containerName, 'images/WindowsAzure1.gif', self::$path . 'WindowsAzure.gif');
            $storageClient->putBlob($containerName, 'images/WindowsAzure2.gif', self::$path . 'WindowsAzure.gif');
            $storageClient->putBlob($containerName, 'images/WindowsAzure3.gif', self::$path . 'WindowsAzure.gif');
            $storageClient->putBlob($containerName, 'images/WindowsAzure4.gif', self::$path . 'WindowsAzure.gif');
            $storageClient->putBlob($containerName, 'images/WindowsAzure5.gif', self::$path . 'WindowsAzure.gif');
            
            $result1 = $storageClient->listBlobs($containerName);
            $this->assertEquals(5, count($result1));
            $this->assertEquals('images/WindowsAzure5.gif', $result1[4]->Name);
            
            $result2 = $storageClient->listBlobs($containerName, '', '', 2);
            $this->assertEquals(2, count($result2));
        }
    }
    
    /**
     * Test copy blob
     */
    public function testCopyBlob()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = $this->generateName();
            $storageClient = $this->createStorageInstance();
            $storageClient->createContainer($containerName);
            $source = $storageClient->putBlob($containerName, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
    
            $this->assertEquals($containerName, $source->Container);
            $this->assertEquals('images/WindowsAzure.gif', $source->Name);
            
            $destination = $storageClient->copyBlob($containerName, 'images/WindowsAzure.gif', $containerName, 'images/WindowsAzureCopy.gif');
    
            $this->assertEquals($containerName, $destination->Container);
            $this->assertEquals('images/WindowsAzureCopy.gif', $destination->Name);
        }
    }
    
    /**
     * Test root container
     */
    public function testRootContainer()
    {
        if (TESTS_BLOB_RUNTESTS)  
        {
            $containerName = '$root';
            $storageClient = $this->createStorageInstance();
            $result = $storageClient->createContainer($containerName);
            $this->assertEquals($containerName, $result->Name);
            
            // ACL
            $storageClient->setContainerAcl($containerName, Microsoft_Azure_Storage_Blob::ACL_PUBLIC);
            $acl = $storageClient->getContainerAcl($containerName);
            
            $this->assertEquals(Microsoft_Azure_Storage_Blob::ACL_PUBLIC, $acl);
            
            // Metadata
            $storageClient->setContainerMetadata($containerName, array(
                'createdby' => 'PHPAzure',
            ));
            
            $metadata = $storageClient->getContainerMetadata($containerName);
            $this->assertEquals('PHPAzure', $metadata['createdby']);
            
            // List
            $result = $storageClient->listContainers();
            $this->assertEquals(1, count($result));
            
            // Put blob
            $result = $storageClient->putBlob($containerName, 'WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
   
            $this->assertEquals($containerName, $result->Container);
            $this->assertEquals('WindowsAzure.gif', $result->Name);
            
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
            $storageClient->setBlobMetadata($containerName, 'WindowsAzure.gif', array(
                'createdby' => 'PHPAzure',
            ));
            
            $metadata = $storageClient->getBlobMetadata($containerName, 'WindowsAzure.gif');
            $this->assertEquals('PHPAzure', $metadata['createdby']);
            
            // List blobs
            $result = $storageClient->listBlobs($containerName);
            $this->assertEquals(1, count($result));
            
            // Delete blob
            $storageClient->deleteBlob($containerName, 'WindowsAzure.gif');
            
            $result = $storageClient->listBlobs($containerName);
            $this->assertEquals(0, count($result));
        }
    }
    
    /**
     * Create large file
     * 
     * @return string Filename
     */
    private function _createLargeFile()
    {
        $fileName = tempnam('', 'tst');
        $fp = fopen($fileName, 'w');
        for ($i = 0; $i < Microsoft_Azure_Storage_Blob::MAX_BLOB_SIZE / 1024; $i++)
        {
            fwrite($fp,
            	'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' .
                'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
            );
        }
        fclose($fp);
        return $fileName;
    }
}

// Call Microsoft_Azure_BlobStorageTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_BlobStorageTest::main") {
    Microsoft_Azure_BlobStorageTest::main();
}
