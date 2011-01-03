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

/**
 * Test helpers
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Microsoft_WindowsAzure_AllTests::main');
}

require_once 'Microsoft/WindowsAzure/Credentials/AllTests.php';
require_once 'Microsoft/WindowsAzure/Diagnostics/AllTests.php';
require_once 'Microsoft/WindowsAzure/RetryPolicyTest.php';
require_once 'Microsoft/WindowsAzure/StorageTest.php';
require_once 'Microsoft/WindowsAzure/BlobStorageTest.php';
require_once 'Microsoft/WindowsAzure/BlobStreamTest.php';
require_once 'Microsoft/WindowsAzure/BlobStorageSharedAccessTest.php';
require_once 'Microsoft/WindowsAzure/TableEntityTest.php';
require_once 'Microsoft/WindowsAzure/DynamicTableEntityTest.php';
require_once 'Microsoft/WindowsAzure/TableEntityQueryTest.php';
require_once 'Microsoft/WindowsAzure/TableStorageTest.php';
require_once 'Microsoft/WindowsAzure/QueueStorageTest.php';
require_once 'Microsoft/WindowsAzure/TableSessionHandlerTest.php';
require_once 'Microsoft/WindowsAzure/BlobSessionHandlerTest.php';

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage UnitTests
 * @version    $Id$
 * @copyright  Copyright (c) 2009 - 2011, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_WindowsAzure_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite(__CLASS__);

        $suite->addTest(Microsoft_WindowsAzure_Credentials_AllTests::suite());
        $suite->addTest(Microsoft_WindowsAzure_Diagnostics_AllTests::suite());
        
        $suite->addTestSuite('Microsoft_WindowsAzure_RetryPolicyTest');
        $suite->addTestSuite('Microsoft_WindowsAzure_StorageTest');
        if (TESTS_BLOB_RUNTESTS) {
            $suite->addTestSuite('Microsoft_WindowsAzure_BlobStorageTest');
            $suite->addTestSuite('Microsoft_WindowsAzure_BlobStorageSharedAccessTest');
            $suite->addTestSuite('Microsoft_WindowsAzure_BlobStreamTest');
        }
        if (TESTS_TABLE_RUNTESTS) {
            $suite->addTestSuite('Microsoft_WindowsAzure_TableEntityTest');
            $suite->addTestSuite('Microsoft_WindowsAzure_DynamicTableEntityTest');
            $suite->addTestSuite('Microsoft_WindowsAzure_TableEntityQueryTest');
            $suite->addTestSuite('Microsoft_WindowsAzure_TableStorageTest');
        }
        if (TESTS_QUEUE_RUNTESTS) {
            $suite->addTestSuite('Microsoft_WindowsAzure_QueueStorageTest');
        }
        if (TESTS_SESSIONHANDLER_RUNTESTS) {
            $suite->addTestSuite('Microsoft_WindowsAzure_TableSessionHandlerTest');
            $suite->addTestSuite('Microsoft_WindowsAzure_BlobSessionHandlerTest');
        }
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Microsoft_WindowsAzure_AllTests::main') {
    Microsoft_WindowsAzure_AllTests::main();
}
