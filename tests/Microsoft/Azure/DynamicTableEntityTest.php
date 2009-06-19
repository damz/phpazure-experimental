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
    define('PHPUnit_MAIN_METHOD', 'Microsoft_Azure_DynamicTableEntityTest::main');
}

/**
 * Test helpers
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';
require_once dirname(__FILE__) . '/../../TestConfigurationAzure.php';
require_once 'PHPUnit/Framework/TestCase.php';

/** Microsoft_Azure_Storage_DynamicTableEntity */
require_once 'Microsoft/Azure/Storage/DynamicTableEntity.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage UnitTests
 * @version    $Id: BlobStorageTest.php 14561 2009-05-07 08:05:12Z unknown $
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_Azure_DynamicTableEntityTest extends PHPUnit_Framework_TestCase
{
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Microsoft_Azure_DynamicTableEntityTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * Test constructor
     */
    public function testConstructor()
    {
        $target = new Microsoft_Azure_Storage_DynamicTableEntity('partition1', '000001');
        $this->assertEquals('partition1', $target->getPartitionKey());
        $this->assertEquals('000001',     $target->getRowKey());
    }
    
    /**
     * Test get Azure values
     */
    public function testGetAzureValues()
    {
        $target = new Microsoft_Azure_Storage_DynamicTableEntity('partition1', '000001');
        $target->Name = 'Name';
        $target->Age  = 25;
        $result = $target->getAzureValues();

        $this->assertEquals('Name',       $result[0]->Name);
        $this->assertEquals('Name',       $result[0]->Value);
        $this->assertEquals('Edm.String', $result[0]->Type);
        
        $this->assertEquals('Age',        $result[1]->Name);
        $this->assertEquals(25,           $result[1]->Value);
        $this->assertEquals('Edm.Int32',  $result[1]->Type);
        
        $this->assertEquals('partition1', $result[2]->Value);
        $this->assertEquals('000001',     $result[3]->Value);
    }
    
    /**
     * Test set Azure values
     */
    public function testSetAzureValuesSuccess()
    {
        $values = array(
            'PartitionKey' => 'partition1',
            'RowKey' => '000001',
            'Name' => 'Maarten',
            'Age' => 25,
            'Visible' => true
        );
        
        $target = new Microsoft_Azure_Storage_DynamicTableEntity();
        $target->setAzureValues($values);
        $target->setAzurePropertyType('Age', 'Edm.Int32');

        $this->assertEquals('partition1', $target->getPartitionKey());
        $this->assertEquals('000001',     $target->getRowKey());
        $this->assertEquals('Maarten',    $target->Name);
        $this->assertEquals(25,           $target->Age);
        $this->assertEquals('Edm.Int32',  $target->getAzurePropertyType('Age'));
        $this->assertEquals(true,         $target->Visible);
    }
}

// Call Microsoft_Azure_DynamicTableEntityTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_DynamicTableEntityTest::main") {
    Microsoft_Azure_DynamicTableEntityTest::main();
}
