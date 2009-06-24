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

/**
 * Test helpers
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';
require_once dirname(__FILE__) . '/../../TestConfiguration.php';
require_once 'PHPUnit/Framework/TestCase.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Microsoft_Azure_AllTests::main');
}

require_once 'Microsoft/Azure/SharedKeyCredentialsTest.php';
require_once 'Microsoft/Azure/SharedKeyLiteCredentialsTest.php';
require_once 'Microsoft/Azure/RetryPolicyTest.php';
require_once 'Microsoft/Azure/StorageTest.php';
require_once 'Microsoft/Azure/BlobStorageTest.php';
require_once 'Microsoft/Azure/TableEntityTest.php';
require_once 'Microsoft/Azure/DynamicTableEntityTest.php';
require_once 'Microsoft/Azure/TableEntityQueryTest.php';
require_once 'Microsoft/Azure/TableStorageTest.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage UnitTests
 * @version    $Id$
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_Azure_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Microsoft Azure');

        $suite->addTestSuite('Microsoft_Azure_SharedKeyCredentialsTest');
        $suite->addTestSuite('Microsoft_Azure_SharedKeyLiteCredentialsTest');
        $suite->addTestSuite('Microsoft_Azure_RetryPolicyTest');
        $suite->addTestSuite('Microsoft_Azure_StorageTest');
        if (TESTS_BLOB_RUNTESTS)
        {
            $suite->addTestSuite('Microsoft_Azure_BlobStorageTest');
        }
        if (TESTS_TABLE_RUNTESTS)
        {
            $suite->addTestSuite('Microsoft_Azure_TableEntityTest');
            $suite->addTestSuite('Microsoft_Azure_DynamicTableEntityTest');
            $suite->addTestSuite('Microsoft_Azure_TableEntityQueryTest');
            $suite->addTestSuite('Microsoft_Azure_TableStorageTest');
        }
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Microsoft_Azure_AllTests::main') {
    Microsoft_Azure_AllTests::main();
}
