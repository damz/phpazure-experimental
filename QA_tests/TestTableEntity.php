<?php

/**
 * Table entity with kinds of field.
 * 
 * Edm.Binary - An array of bytes up to 64 KB in size.
 * Edm.Boolean - A boolean value.
 * Edm.DateTime - A 64-bit value expressed as Coordinated Universal Time (UTC). The supported DateTime range begins from 12:00 midnight, January 1, 1601 A.D. (C.E.), Coordinated Universal Time (UTC). The range ends at December 31st, 9999.
 * Edm.Double - A 64-bit floating point value.
 * Edm.Guid - A 128-bit globally unique identifier.
 * Edm.Int32 - A 32-bit integer.
 * Edm.Int64 - A 64-bit integer.
 * Edm.String - A UTF-16-encoded value. String values may be up to 64 KB in size.
 */
class Test_TableEntity extends Microsoft_WindowsAzure_Storage_TableEntity {
	
	/**
	 * you can get a valid date format like $date_string = date("Y-m-d\\TH:i:s");
	 * @azure dateField Edm.DateTime
	 * 
	 */
	public $dateField = "2009-07-13T07:06:05.8659709Z"; //default
	

	/**
	 * 
	 * @azure stringField Edm.String
	 */
	public $stringField = "hello ketty";
	
	/**
	 * 
	 * @azure binaryField Edm.Binary
	 */
	public $binaryField = "SSBsb3ZlIHlvdQ==";
	
	/**
	 * @azure booleanField Edm.Boolean
	 *
	 */
	public $booleanField = true;
	
	/**
	 * @azure doubleField Edm.Double
	 *
	 */
	public $doubleField = 21.0;
	
	/**
	 * 
	 * @azure int32Field Edm.Int32
	 */
	public $int32Field = 32;
	
	/**
	 * 
	 * @azure int64Field Edm.Int64
	 */
	public $int64Field = 64;
	
	/**
	 * 
	 * @azure guidField Edm.Guid  
	 */
	public $guidField = "31393535353038313937373830383630";
}
?>