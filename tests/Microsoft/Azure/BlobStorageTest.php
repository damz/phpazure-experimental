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
 * Test helper
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';

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
        $suite  = new PHPUnit_Framework_TestSuite("Microsoft_Azure_BlobStorageTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * Test setup
     */
    protected function setUp()
    {
        // Ensure container is removed
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        try { $storageClient->deleteContainer(TESTS_BLOB_CONTAINERNAME); } catch (Exception $ex) { }
        try { $storageClient->deleteContainer(TESTS_BLOB_CONTAINERNAME_EXTRA_1); } catch (Exception $ex) { }
        try { $storageClient->deleteContainer(TESTS_BLOB_CONTAINERNAME_EXTRA_2); } catch (Exception $ex) { }
        try { $storageClient->deleteContainer(TESTS_BLOB_CONTAINERNAME_EXTRA_3); } catch (Exception $ex) { }
        try { $storageClient->deleteContainer(TESTS_BLOB_CONTAINERNAME_EXTRA_4); } catch (Exception $ex) { }
    }

    /**
     * Test create container
     */
    public function testCreateContainer()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $result = $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        $this->assertEquals(TESTS_BLOB_CONTAINERNAME, $result->Name);
    }
    
    /**
     * Test get container acl
     */
    public function testGetContainerAcl()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        $acl = $storageClient->getContainerAcl(TESTS_BLOB_CONTAINERNAME);
        $this->assertEquals(Microsoft_Azure_Storage_Blob::ACL_PRIVATE, $acl);        
    }
    
    /**
     * Test set container acl
     */
    public function testSetContainerAcl()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        
        $storageClient->setContainerAcl(TESTS_BLOB_CONTAINERNAME, Microsoft_Azure_Storage_Blob::ACL_PUBLIC);
        $acl = $storageClient->getContainerAcl(TESTS_BLOB_CONTAINERNAME);
        
        $this->assertEquals(Microsoft_Azure_Storage_Blob::ACL_PUBLIC, $acl);
    }
    
    /**
     * Test set container metadata
     */
    public function testSetContainerMetadata()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        
        $storageClient->setContainerMetadata(TESTS_BLOB_CONTAINERNAME, array(
            'createdby' => 'PHPAzure',
        ));
        
        $metadata = $storageClient->getContainerMetadata(TESTS_BLOB_CONTAINERNAME);
        $this->assertEquals('PHPAzure', $metadata['createdby']);
    }
    
    /**
     * Test list containers
     */
    public function testListContainers()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME_EXTRA_1);
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME_EXTRA_2);
        $result = $storageClient->listContainers();

        $this->assertEquals(3, count($result));
        $this->assertEquals(TESTS_BLOB_CONTAINERNAME, $result[0]->Name);
    }
    
    /**
     * Test put blob
     */
    public function testPutBlob()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        $result = $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');

        $this->assertEquals(TESTS_BLOB_CONTAINERNAME, $result->Container);
        $this->assertEquals('images/WindowsAzure.gif', $result->Name);
    }
    
    /**
     * Test put large blob
     */
    public function testPutLargeBlob()
    {
        // Create a file > Microsoft_Azure_Storage_Blob::MAX_BLOB_SIZE
        $fileName = $this->_createLargeFile();
        
        // Execute test
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        $result = $storageClient->putLargeBlob(TESTS_BLOB_CONTAINERNAME, 'LargeFile.txt', $fileName);

        $this->assertEquals(TESTS_BLOB_CONTAINERNAME, $result->Container);
        $this->assertEquals('LargeFile.txt', $result->Name);
        
        // Remove file
        unlink($fileName);
    }
    
    /**
     * Test get blob
     */
    public function testGetBlob()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
        
        $fileName = tempnam('', 'tst');
        $storageClient->getBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif', $fileName);

        $this->assertTrue(file_exists($fileName));
        $this->assertEquals(
            file_get_contents(self::$path . 'WindowsAzure.gif'),
            file_get_contents($fileName)
        );
        
        // Remove file
        unlink($fileName);
    }
    
    /**
     * Test set blob metadata
     */
    public function testSetBlobMetadata()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
        
        $storageClient->setBlobMetadata(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif', array(
            'createdby' => 'PHPAzure',
        ));
        
        $metadata = $storageClient->getBlobMetadata(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif');
        $this->assertEquals('PHPAzure', $metadata['createdby']);
    }
    
    /**
     * Test delete blob
     */
    public function testDeleteBlob()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif', self::$path . 'WindowsAzure.gif');
        $storageClient->deleteBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure.gif');
        
        $result = $storageClient->listBlobs(TESTS_BLOB_CONTAINERNAME);
        $this->assertEquals(0, count($result));
    }
    
    /**
     * Test list blobs
     */
    public function testListBlobs()
    {
        $storageClient = new Microsoft_Azure_Storage_Blob(TESTS_BLOB_HOST, TESTS_STORAGE_ACCOUNT, TESTS_STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN(10, 250));
        $storageClient->createContainer(TESTS_BLOB_CONTAINERNAME);
        
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure1.gif', self::$path . 'WindowsAzure.gif');
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure2.gif', self::$path . 'WindowsAzure.gif');
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure3.gif', self::$path . 'WindowsAzure.gif');
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure4.gif', self::$path . 'WindowsAzure.gif');
        $storageClient->putBlob(TESTS_BLOB_CONTAINERNAME, 'images/WindowsAzure5.gif', self::$path . 'WindowsAzure.gif');
        
        $result = $storageClient->listBlobs(TESTS_BLOB_CONTAINERNAME);
        $this->assertEquals(5, count($result));
        $this->assertEquals('images/WindowsAzure5.gif', $result[4]->Name);
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
