<?php
/**
 * Copyright (c) 2009 - 2011, RealDolmen
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
 * @package    Microsoft_WindowsAzure
 * @subpackage UnitTests
 * @version    $Id$
 * @copyright  Copyright (c) 2009 - 2011, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_StorageTest::main');
}

/**
 * Test helpers
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';
require_once dirname(__FILE__) . '/../../TestConfiguration.php';
require_once 'PHPUnit/Framework/TestCase.php';

/** Microsoft_WindowsAzure_Storage */
require_once 'Microsoft/WindowsAzure/Storage.php';

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage UnitTests
 * @version    $Id$
 * @copyright  Copyright (c) 2009 - 2011, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_WindowsAzure_StorageTest extends PHPUnit_Framework_TestCase
{
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Microsoft_WindowsAzure_BlobStorageTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Test constructor for devstore
     */
    public function testConstructorForDevstore()
    {
        $storage = new Microsoft_WindowsAzure_Storage();
        $this->assertEquals('http://127.0.0.1:10000/devstoreaccount1', $storage->getBaseUrl());
    }
    
    /**
     * Test constructor for production
     */
    public function testConstructorForProduction()
    {
        $storage = new Microsoft_WindowsAzure_Storage(Microsoft_WindowsAzure_Storage::URL_CLOUD_BLOB, 'testing', '');
        $this->assertEquals('http://testing.blob.core.windows.net', $storage->getBaseUrl());
    }
}

// Call Microsoft_WindowsAzure_StorageTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_WindowsAzure_StorageTest::main") {
    Microsoft_WindowsAzure_StorageTest::main();
}
