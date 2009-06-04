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
 * @package    UnitTests
 * @version    $Id$
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

/**
 * PHPUnit Code Coverage / Test Report
 */
define('TESTS_GENERATE_REPORT',        false);
define('TESTS_GENERATE_REPORT_TARGET', '/path/to/target');

/**
 * Azure hosts
 */
define('TESTS_BLOB_HOST_DEV',          '127.0.0.1:10000');
define('TESTS_QUEUE_HOST_DEV',         '127.0.0.1:10001');
define('TESTS_TABLE_HOST_DEV',         '127.0.0.1:10002');
define('TESTS_BLOB_HOST_PROD',         'blob.core.windows.net');
define('TESTS_QUEUE_HOST_PROD',        'queue.core.windows.net');
define('TESTS_TABLE_HOST_PROD',        'table.core.windows.net');

/**
 * Credentials
 */
define('TESTS_STORAGE_ACCOUNT_DEV',    'devstoreaccount1');
define('TESTS_STORAGE_KEY_DEV',        'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==');
define('TESTS_STORAGE_ACCOUNT_PROD',   'phpstorage');
define('TESTS_STORAGE_KEY_PROD',       'WXuEUKMijV/pxUu5/RhDn1bYRuFlLSbmLUJJWRqYQ/uxbMpEx+7S/jo9sT3ZIkEucZGbEafDuxD1kwFOXf3xyw==');

/**
 * Blob storage tests
 */
define('TESTS_BLOB_RUNTESTS',           true);
define('TESTS_BLOB_RUNONPROD',          true);
define('TESTS_BLOB_CONTAINER_PREFIX',   'phpazuretest');

/**
 * Table storage tests
 */
define('TESTS_TABLE_RUNTESTS',          true);
define('TESTS_TABLE_RUNONPROD',         true);
define('TESTS_TABLE_TABLENAME_PREFIX',  'phpazuretest');
define('TESTS_TABLE_DEVCNSTRING',       'Driver={SQL Native Client};Server=.\SQLEXPRESS;Database=AzureTableStorage; Uid=php;Pwd=php;');
define('TESTS_TABLE_DEVCNUSER',         'php');
define('TESTS_TABLE_DEVCNPASS',         'php');
