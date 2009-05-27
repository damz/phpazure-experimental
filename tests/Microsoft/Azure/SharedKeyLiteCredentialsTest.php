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
 * @version    $Id: SharedKeyCredentialsTest.php 14561 2009-05-07 08:05:12Z unknown $
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Microsoft_Azure_SharedKeyLiteCredentialsTest::main');
}

/**
 * Test helper
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';

/** Microsoft_Azure_SharedKeyLiteCredentials */
require_once 'Microsoft/Azure/SharedKeyLiteCredentials.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage UnitTests
 * @version    $Id: SharedKeyCredentialsTest.php 14561 2009-05-07 08:05:12Z unknown $
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_Azure_SharedKeyLiteCredentialsTest extends PHPUnit_Framework_TestCase
{
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Microsoft_Azure_SharedKeyLiteCredentialsTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Test signing for devstore with root path
     */
    public function testSignForDevstoreWithRootPath()
    {
        $credentials = new Microsoft_Azure_SharedKeyLiteCredentials(Microsoft_Azure_SharedKeyCredentials::DEVSTORE_ACCOUNT, Microsoft_Azure_SharedKeyCredentials::DEVSTORE_KEY, true);
        $signedHeaders = $credentials->signRequest(
                              'GET',
                              '/',
                              '',
                              array("x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"),
                              false
                          );
                          
        $this->assertType('array', $signedHeaders);
        $this->assertEquals(2, count($signedHeaders));
        $this->assertEquals("SharedKeyLite devstoreaccount1:iRQpXGzlMRb1A57bkcryX7Bg/3Uf5YOfNCG+XIingJI=", $signedHeaders["Authorization"]);
    }
    
    /**
     * Test signing for devstore with other path
     */
    public function testSignForDevstoreWithOtherPath()
    {
        $credentials = new Microsoft_Azure_SharedKeyLiteCredentials(Microsoft_Azure_SharedKeyCredentials::DEVSTORE_ACCOUNT, Microsoft_Azure_SharedKeyCredentials::DEVSTORE_KEY, true);
        $signedHeaders = $credentials->signRequest(
                              'GET',
                              '/test',
                              '',
                              array("x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"),
                              false
                          );
  
        $this->assertType('array', $signedHeaders);
        $this->assertEquals(2, count($signedHeaders));
        $this->assertEquals("SharedKeyLite devstoreaccount1:MsC5SIbFB4M4UZd83CiMaL8ibUhaS5H9CcJBJpsnWqo=", $signedHeaders["Authorization"]);
    }
    
    /**
     * Test signing for devstore with query string
     */
    public function testSignForDevstoreWithQueryString()
    {
        $credentials = new Microsoft_Azure_SharedKeyLiteCredentials(Microsoft_Azure_SharedKeyCredentials::DEVSTORE_ACCOUNT, Microsoft_Azure_SharedKeyCredentials::DEVSTORE_KEY, true);
        $signedHeaders = $credentials->signRequest(
                              'GET',
                              '/',
                              '?test=true',
                              array("x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"),
                              false
                          );
  
        $this->assertType('array', $signedHeaders);
        $this->assertEquals(2, count($signedHeaders));
        $this->assertEquals("SharedKeyLite devstoreaccount1:3hRhJfWP8LIEYidrMPsl55soq6V8GvXDcvLm3vgt4C8=", $signedHeaders["Authorization"]);
    }
    
    /**
     * Test signing for production with root path
     */
    public function testSignForProductionWithRootPath()
    {
        $credentials = new Microsoft_Azure_SharedKeyLiteCredentials('testing', 'abcdefg');
        $signedHeaders = $credentials->signRequest(
                              'GET',
                              '/',
                              '',
                              array("x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"),
                              false
                          );
                          
        $this->assertType('array', $signedHeaders);
        $this->assertEquals(2, count($signedHeaders));
        $this->assertEquals("SharedKeyLite testing:vZdOn/j0gW5FG0kAUG9NhSBO9eBjZqfe6RwALPYUtqU=", $signedHeaders["Authorization"]);
    }
    
    /**
     * Test signing for production with other path
     */
    public function testSignForProductionWithOtherPath()
    {
        $credentials = new Microsoft_Azure_SharedKeyLiteCredentials('testing', 'abcdefg');
        $signedHeaders = $credentials->signRequest(
                              'GET',
                              '/test',
                              '',
                              array("x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"),
                              false
                          );
  
        $this->assertType('array', $signedHeaders);
        $this->assertEquals(2, count($signedHeaders));
        $this->assertEquals("SharedKeyLite testing:HJTSiRDtMsQVsFVispSHkcODeFykLO+WEuOepwmh51o=", $signedHeaders["Authorization"]);
    }
    
    /**
     * Test signing for production with query string
     */
    public function testSignForProductionWithQueryString()
    {
        $credentials = new Microsoft_Azure_SharedKeyLiteCredentials('testing', 'abcdefg');
        $signedHeaders = $credentials->signRequest(
                              'GET',
                              '/',
                              '?test=true',
                              array("x-ms-date" => "Wed, 29 Apr 2009 13:12:47 GMT"),
                              false
                          );
  
        $this->assertType('array', $signedHeaders);
        $this->assertEquals(2, count($signedHeaders));
        $this->assertEquals("SharedKeyLite testing:g9tSOs3u9PJfT+/L6AnVX2e2VzhJrzAkK/bWoukw4NY=", $signedHeaders["Authorization"]);
    }
}

// Call Microsoft_Azure_SharedKeyLiteCredentialsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Microsoft_Azure_SharedKeyLiteCredentialsTest::main") {
    Microsoft_Azure_SharedKeyLiteCredentialsTest::main();
}