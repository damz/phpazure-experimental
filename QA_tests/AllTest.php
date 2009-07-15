<?php
/**
 * Copyright (c) 2009, Soyatec
 * All rights reserved.
 *
 * @category   Microsoft
 * @package    UnitTests
 * @version    $Id$
 * @copyright  Copyright (c) 2009, Soyatec (http://www.soyatec.com)
 * @license    http://www.soyatec.com
 */

if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'AllTests::main' );
}

require_once 'PHPUnit/Framework.php';
require_once 'BlobLocalTest.php';

/**
 * @category   Microsoft
 * @package    UnitTests
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class AllTests extends PHPUnit_Framework_TestCase {
	public static function main() {
		PHPUnit_TextUI_TestRunner::run ( self::suite () );
	}
	
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite ( 'AllTests' );
		
		/**
		 * Blob remote service test.
		 */
		$suite->addTestSuite ( 'Microsoft_Azure_BlobTest' );
		
		/**
		 * Blob local test.
		 */
		$suite->addTestSuite ( 'Microsoft_Azure_BlobLocalTest' );
		
		/**
		 * Table remove server test
		 */
		$suite->addTestSuite ( 'Microsoft_Azure_TableTest' );
		
		
		return $suite;
	}
	
	public function testScaffoldwork() {
		$abc = 'abc';
		$this->assertEquals ( 'abc', $abc );
	}
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
	AllTests::main ();
}
?>