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
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: Blob.php 14561 2009-05-07 08:05:12Z unknown $
 */

/**
 * @see Microsoft_Azure_Credentials
 */
require_once 'Microsoft/Azure/Credentials.php';

/**
 * @see Microsoft_Azure_SharedKeyCredentials
 */
require_once 'Microsoft/Azure/SharedKeyCredentials.php';

/**
 * @see Microsoft_Azure_SharedKeyLiteCredentials
 */
require_once 'Microsoft/Azure/SharedKeyLiteCredentials.php';

/**
 * @see Microsoft_Azure_RetryPolicy
 */
require_once 'Microsoft/Azure/RetryPolicy.php';

/**
 * @see Microsoft_Http_Transport
 */
require_once 'Microsoft/Http/Transport.php';

/**
 * @see Microsoft_Http_Response
 */
require_once 'Microsoft/Http/Response.php';

/**
 * @see Microsoft_Azure_Storage
 */
require_once 'Microsoft/Azure/Storage.php';

/**
 * @see Microsoft_Azure_Storage_TableInstance
 */
require_once 'Microsoft/Azure/Storage/TableInstance.php';

/**
 * @see Microsoft_Azure_Exception
 */
require_once 'Microsoft/Azure/Exception.php';


/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_Azure_Storage_Table extends Microsoft_Azure_Storage
{
	/**
	 * Dummy
	 */
	const DUMMY = 1234;
	
	/**
	 * Creates a new Microsoft_Azure_Storage_Table instance
	 *
	 * @param string $host Storage host name
	 * @param string $accountName Account name for Windows Azure
	 * @param string $accountKey Account key for Windows Azure
	 * @param boolean $usePathStyleUri Use path-style URI's
	 * @param Microsoft_Azure_RetryPolicy $retryPolicy Retry policy to use when making requests
	 */
	public function __construct($host = Microsoft_Azure_Storage::URL_DEV_TABLE, $accountName = Microsoft_Azure_Credentials::DEVSTORE_ACCOUNT, $accountKey = Microsoft_Azure_Credentials::DEVSTORE_KEY, $usePathStyleUri = false, Microsoft_Azure_RetryPolicy $retryPolicy = null)
	{
		parent::__construct($host, $accountName, $accountKey, $usePathStyleUri, $retryPolicy);
		if ($host == Microsoft_Azure_Storage::URL_DEV_TABLE)
	    {
	        // Use SharedKeyLite authentication on development storage
	        $this->_credentials = new Microsoft_Azure_SharedKeyLiteCredentials($accountName, $accountKey, $this->_usePathStyleUri);
	    }
	}

	/**
	 * List tables
	 *
	 * @return array
	 * @throws Microsoft_Azure_Exception
	 */
	public function listTables()
	{
		// Perform request
		$response = $this->performRequest('Tables', '', Microsoft_Http_Transport::VERB_GET, null, true);
		if ($response->isSuccessful())
		{
		    // TODO: Use NextTableName when working with > 1000 tables (http://msdn.microsoft.com/en-us/library/dd179405.aspx)
		    
		    // Parse result
		    $result = $this->parseResponse($response);	
		    
		    if (!$result->entry)
		        return array();
		        
		    $entries = null;
		    if (count($result->entry) > 1) {
		        $entries = $result->entry;
		    } else {
		        $entries = array($result->entry);
		    }

		    // Create return value
		    $returnValue = array();		    
		    foreach ($entries as $entry)
		    {
		        $tableName = $entry->xpath('.//m:properties/d:TableName');
		        $tableName = (string)$tableName[0];
		        
		        $returnValue[] = new Microsoft_Azure_Storage_TableInstance(
		            (string)$entry->id,
		            $tableName,
		            (string)$entry->link['href'],
		            (string)$entry->updated
		        );
		    } 

		    return $returnValue;
		}
		else
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->message);
		}
	}
	
	/**
	 * Create table
	 *
	 * @param string $tableName Table name
	 * @return Microsoft_Azure_Storage_TableInstance
	 * @throws Microsoft_Azure_Exception
	 */
	public function createTable($tableName = '')
	{
		if ($tableName === '')
			throw new Microsoft_Azure_Exception('Table name is not specified.');
			
		// Generate request body
		$requestBody = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                        <entry xml:base="{tpl:BaseUrl}" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
                          <id>{tpl:BaseUrl}/Tables(\'{tpl:TableName}\')</id>
                          <title type="text"></title>
                          <updated>{tpl:Updated}</updated>
                          <author>
                            <name />
                          </author>
                          <link rel="edit" title="Tables" href="Tables(\'{tpl:TableName}\')" />
                          <category term="{tpl:AccountName}.Tables" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" />
                          <content type="application/xml">
                            <m:properties>
                              <d:TableName>{tpl:TableName}</d:TableName>
                            </m:properties>
                          </content>
                        </entry>';
		
        $requestBody = $this->fillTemplate($requestBody, array(
            'BaseUrl' => $this->getBaseUrl(),
            'TableName' => $tableName,
        	'Updated' => $this->isoDate(),
            'AccountName' => $this->_accountName
        ));

        // Add header information
        $headers = array();
        $headers['Content-Type'] = 'application/atom+xml';

		// Perform request
		$response = $this->performRequest('Tables', '', Microsoft_Http_Transport::VERB_POST, $headers, null, $requestBody);
		if ($response->isSuccessful())
		{
		    // Parse response
		    $entry = $this->parseResponse($response);
		    
		    $tableName = $entry->xpath('.//m:properties/d:TableName');
		    $tableName = (string)$tableName[0];
		        
		    return new Microsoft_Azure_Storage_TableInstance(
		        (string)$entry->id,
		        $tableName,
		        (string)$entry->link['href'],
		        (string)$entry->updated
		    );
		}
		else
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->message);
		}
	}
	
	/**
	 * Delete table
	 *
	 * @param string $tableName Table name
	 * @throws Microsoft_Azure_Exception
	 */
	public function deleteTable($tableName = '')
	{
		if ($tableName === '')
			throw new Microsoft_Azure_Exception('Table name is not specified.');

        // Add header information
        $headers = array();
        $headers['Content-Type'] = 'application/atom+xml';

		// Perform request
		$response = $this->performRequest('Tables(\'' . $tableName . '\')', '', Microsoft_Http_Transport::VERB_DELETE, $headers, null, null);
		if (!$response->isSuccessful())
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->message);
		}
	}
	
	/**
	 * Generate RFC 1123 compliant date string
	 * 
	 * @return string
	 */
	protected function rfcDate()
	{
	    return gmdate('D, d M Y H:i:s', time()) . ' GMT'; // RFC 1123
	}
	
	/**
	 * Generate ISO 8601 compliant date string in UTC time zone
	 * 
	 * @return string
	 */
	protected function isoDate() 
	{
	    $tz = @date_default_timezone_get();
	    @date_default_timezone_set('UTC');
	    $returnValue = str_replace('+00:00', 'Z', @date('c'));
	    @date_default_timezone_set($tz);
	    return $returnValue;
	}
	
	/**
	 * Fill text template with variables from key/value array
	 * 
	 * @param string $templateText Template text
	 * @param array $variables Array containing key/value pairs
	 * @return string
	 */
	protected function fillTemplate($templateText, $variables = array())
	{
	    foreach ($variables as $key => $value)
	    {
	        $templateText = str_replace('{tpl:' . $key . '}', $value, $templateText);
	    }
	    return $templateText;
	}
}
