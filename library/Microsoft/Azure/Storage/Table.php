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
 * @see Microsoft_Azure_Storage_TableEntity
 */
require_once 'Microsoft/Azure/Storage/TableEntity.php';

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
     * ODBC connection string
     * 
     * @var string
     */
    protected $_odbcConnectionString;
    
    /**
     * ODBC user name
     * 
     * @var string
     */
    protected $_odbcUsername;
    
    /**
     * ODBC password
     * 
     * @var string
     */
    protected $_odbcPassword;
    
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
	 * Set ODBC connection settings - used for creating tables on development storage
	 * 
	 * @param string $connectionString  ODBC connection string
	 * @param string $username          ODBC user name
	 * @param string $password          ODBC password
	 */
	public function setOdbcSettings($connectionString, $username, $password)
	{
	    $this->_odbcConnectionString = $connectionString;
	    $this->_odbcUserame = $username;
	    $this->_odbcPassword = $password;
	}

	/**
	 * Generate table on development storage
	 * 
	 * Note 1: ODBC settings must be specified first using setOdbcSettings()
	 * Note 2: Development table storage MST BE RESTARTED after generating tables. Otherwise newly create tables will NOT be accessible!
	 * 
	 * @param string $entityClass Entity class name
	 * @param string $tableName   Table name
	 */
	public function generateDevelopmentTable($entityClass, $tableName)
	{
	    // Check if we can do this...
	    if (!function_exists('odbc_connect'))
	        throw new Microsoft_Azure_Exception('Function odbc_connect does not exist. Please enable the php_odbc.dll module in your php.ini.');
	    if ($this->_odbcConnectionString == '' || $this->_odbcUserame == '' || $this->_odbcPassword == '')
	        throw new Microsoft_Azure_Exception('Please specify ODBC settings first using setOdbcSettings().');
	    if ($entityClass === '')
			throw new Microsoft_Azure_Exception('Entity class is not specified.');
			
	    // Get accessors
	    $accessors = Microsoft_Azure_Storage_TableEntity::getAzureAccessors($entityClass);
	    
	    // Generate properties
	    $properties = array();
	    foreach ($accessors as $accessor)
	    {
	        if ($accessor->AzurePropertyName == 'Timestamp'
	            || $accessor->AzurePropertyName == 'PartitionKey'
	            || $accessor->AzurePropertyName == 'RowKey')
	            {
	                continue;
	            }

	        switch (strtolower($accessor->AzurePropertyType))
	        {
	            case 'edm.int32':
	            case 'edm.int64':
	                $sqlType = '[int] NULL'; break;
	            case 'edm.guid':
	                $sqlType = '[uniqueidentifier] NULL'; break;
	            case 'edm.datetime':
	                $sqlType = '[datetime] NULL'; break;
	            case 'edm.boolean':
	                $sqlType = '[bit] NULL'; break;
	            case 'edm.double':
	                $sqlType = '[decimal] NULL'; break;
	            default:
	                $sqlType = '[nvarchar](1000) NULL'; break;
	        }
	        $properties[] = '[' . $accessor->AzurePropertyName . '] ' . $sqlType;
	    }
	    
	    // Generate SQL
	    $sql = 'CREATE TABLE [dbo].[{tpl:TableName}](
                	{tpl:Properties} {tpl:PropertiesComma}
                	[Timestamp] [datetime] NULL,
                	[PartitionKey] [nvarchar](1000) NOT NULL,
                	[RowKey] [nvarchar](1000) NOT NULL
                )';

        $sql = $this->fillTemplate($sql, array(
            'TableName'       => $tableName,
        	'Properties'      => implode(',', $properties),
        	'PropertiesComma' => count($properties) > 0 ? ',' : ''
        ));
        
        // Connect to database
        $db = @odbc_connect($this->_odbcConnectionString, $this->_odbcUserame, $this->_odbcPassword);
        if (!$db)
        {
            throw new Microsoft_Azure_Exception('Could not connect to database via ODBC.');
        }
        
        // Create table
        odbc_exec($db, $sql);
        
        // Close connection
        odbc_close($db);
	}
	
	/**
	 * List tables
	 *
	 * @param  string $nextTableName Next table name, used for listing tables when total amount of tables is > 1000.
	 * @return array
	 * @throws Microsoft_Azure_Exception
	 */
	public function listTables($nextTableName = '')
	{
		// Perform request
		$response = $this->performRequest('Tables', '', Microsoft_Http_Transport::VERB_GET, null, true);
		if ($response->isSuccessful())
		{	    
		    // Parse result
		    $result = $this->parseResponse($response);	
		    
		    if (!$result || !$result->entry)
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
		    
			// More tables?
		    if (!is_null($response->getHeader('x-ms-continuation-NextTableName')))
		    {
		        $returnValue = array_merge($returnValue, $this->listTables($response->getHeader('x-ms-continuation-NextTableName')));
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
		$response = $this->performRequest('Tables', '', Microsoft_Http_Transport::VERB_POST, $headers, true, $requestBody);
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
		$response = $this->performRequest('Tables(\'' . $tableName . '\')', '', Microsoft_Http_Transport::VERB_DELETE, $headers, true, null);
		if (!$response->isSuccessful())
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->message);
		}
	}
	
	/**
	 * Insert entity into table
	 * 
	 * @param string                              $tableName   Table name
	 * @param Microsoft_Azure_Storage_TableEntity $entity      Entity to insert
	 * @return Microsoft_Azure_Storage_TableEntity
	 * @throws Microsoft_Azure_Exception
	 */
	public function insertEntity($tableName = '', Microsoft_Azure_Storage_TableEntity $entity = null)
	{
		if ($tableName === '')
			throw new Microsoft_Azure_Exception('Table name is not specified.');
		if (is_null($entity))
			throw new Microsoft_Azure_Exception('Entity is not specified.');
		                     
		// Generate request body
		$requestBody = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                        <entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
                          <title />
                          <updated>{tpl:Updated}</updated>
                          <author>
                            <name />
                          </author>
                          <id />
                          <content type="application/xml">
                            <m:properties>
                              {tpl:Properties}
                            </m:properties>
                          </content>
                        </entry>';
		
        $requestBody = $this->fillTemplate($requestBody, array(
        	'Updated'    => $this->isoDate(),
            'Properties' => $this->generateAzureRepresentation($entity)
        ));

        // Add header information
        $headers = array();
        $headers['Content-Type'] = 'application/atom+xml';

		// Perform request
		$response = $this->performRequest($tableName, '', Microsoft_Http_Transport::VERB_POST, $headers, true, $requestBody);
		if ($response->isSuccessful())
		{
		    // Parse result
		    $result = $this->parseResponse($response);
		    
		    $timestamp = $result->xpath('//m:properties/d:Timestamp');
		    $timestamp = (string)$timestamp[0];

		    $etag      = $result->attributes('http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
		    $etag      = (string)$etag['etag'];
		    
		    // Update properties
		    $entity->setTimestamp($timestamp);
		    $entity->setEtag($etag);

		    return $entity;
		}
		else
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->message);
		}
	}
	
	/**
	 * Delete entity from table
	 * 
	 * @param string                              $tableName   Table name
	 * @param Microsoft_Azure_Storage_TableEntity $entity      Entity to delete
	 * @param boolean                             $verifyEtag  Verify etag of the entity (used for concurrency)
	 * @throws Microsoft_Azure_Exception
	 */
	public function deleteEntity($tableName = '', Microsoft_Azure_Storage_TableEntity $entity = null, $verifyEtag = false)
	{
		if ($tableName === '')
			throw new Microsoft_Azure_Exception('Table name is not specified.');
		if (is_null($entity))
			throw new Microsoft_Azure_Exception('Entity is not specified.');
		                     
        // Add header information
        $headers = array();
        $headers['Content-Type']   = 'application/atom+xml';
        $headers['Content-Length'] = 0;
        if (!$verifyEtag) {
            $headers['If-Match']       = '*';
        } else {
            $headers['If-Match']       = $entity->getEtag();
        }

		// Perform request
		$response = $this->performRequest($tableName . '(PartitionKey=\'' . $entity->getPartitionKey() . '\', RowKey=\'' . $entity->getRowKey() . '\')', '', Microsoft_Http_Transport::VERB_DELETE, $headers, true, null);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->message);
		}
	}
	
	/**
	 * Retrieve entity from table, by id
	 * 
	 * @param string $entityClass  Entity class name
	 * @param string $tableName    Table name
	 * @param string $partitionKey Partition key
	 * @param string $rowKey       Row key
	 * @return Microsoft_Azure_Storage_TableEntity
	 * @throws Microsoft_Azure_Exception
	 */
	public function retrieveEntityById($entityClass = '', $tableName = '', $partitionKey = '', $rowKey = '')
	{
		if ($entityClass === '')
			throw new Microsoft_Azure_Exception('Entity class is not specified.');
		if ($tableName === '')
			throw new Microsoft_Azure_Exception('Table name is not specified.');
		if ($partitionKey === '')
			throw new Microsoft_Azure_Exception('Partition key is not specified.');
		if ($rowKey === '')
			throw new Microsoft_Azure_Exception('Row key is not specified.');
		                     
		// Perform request
		$response = $this->performRequest($tableName . '(PartitionKey=\'' . $partitionKey . '\', RowKey=\'' . $rowKey . '\')', '', Microsoft_Http_Transport::VERB_GET, array(), true, null);
		if ($response->isSuccessful())
		{
		    // Parse result
		    $result = $this->parseResponse($response);
		    if (!$result)
		        return null;

		    // Parse properties
		    $properties = $result->xpath('.//m:properties');
		    $properties = $properties[0]->children('http://schemas.microsoft.com/ado/2007/08/dataservices');
		    
		    // Create entity
		    $entity = new $entityClass('', '');
		    $entity->setAzureValues((array)$properties, true);

		    // Update etag
		    $etag      = $result->attributes('http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
		    $etag      = (string)$etag['etag'];
		    $entity->setEtag($etag);
		    
		    return $entity;
		}
		else
		{
		    throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->message);
		}
	}
	
	/**
	 * Update entity by replacing it
	 * 
	 * @param string                              $tableName   Table name
	 * @param Microsoft_Azure_Storage_TableEntity $entity      Entity to update
	 * @param boolean                             $verifyEtag  Verify etag of the entity (used for concurrency)
	 * @throws Microsoft_Azure_Exception
	 */
	public function updateEntity($tableName = '', Microsoft_Azure_Storage_TableEntity $entity = null, $verifyEtag = false)
	{
		if ($tableName === '')
			throw new Microsoft_Azure_Exception('Table name is not specified.');
		if (is_null($entity))
			throw new Microsoft_Azure_Exception('Entity is not specified.');
		                     
        // Add header information
        $headers = array();
        $headers['Content-Type']   = 'application/atom+xml';
        $headers['Content-Length'] = 0;
        if (!$verifyEtag) {
            $headers['If-Match']       = '*';
        } else {
            $headers['If-Match']       = $entity->getEtag();
        }

	    // Generate request body
		$requestBody = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                        <entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
                          <title />
                          <updated>{tpl:Updated}</updated>
                          <author>
                            <name />
                          </author>
                          <id />
                          <content type="application/xml">
                            <m:properties>
                              {tpl:Properties}
                            </m:properties>
                          </content>
                        </entry>';
		
        $requestBody = $this->fillTemplate($requestBody, array(
        	'Updated'    => $this->isoDate(),
            'Properties' => $this->generateAzureRepresentation($entity)
        ));

        // Add header information
        $headers = array();
        $headers['Content-Type'] = 'application/atom+xml';
	    if (!$verifyEtag) {
            $headers['If-Match']       = '*';
        } else {
            $headers['If-Match']       = $entity->getEtag();
        }
        
		// Perform request
		$response = $this->performRequest($tableName . '(PartitionKey=\'' . $entity->getPartitionKey() . '\', RowKey=\'' . $entity->getRowKey() . '\')', '', Microsoft_Http_Transport::VERB_PUT, $headers, true, $requestBody);
		if ($response->isSuccessful())
		{
		    // Update properties
			$entity->setEtag($response->getHeader('Etag'));
			$entity->setTimestamp($response->getHeader('Last-modified'));

		    return $entity;
		}
		else
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
	
	/**
	 * Generate Azure representation from entity (creates atompub markup from properties)
	 * 
	 * @param Microsoft_Azure_Storage_TableEntity $entity
	 * @return string
	 */
	protected function generateAzureRepresentation(Microsoft_Azure_Storage_TableEntity $entity = null)
	{
		// Generate Azure representation from entity
		$azureRepresentation = array();
		$azureValues         = $entity->getAzureValues();
		foreach ($azureValues as $azureValue)
		{
		    $value = array();
		    $value[] = '<d:' . $azureValue->Name;
		    if ($azureValue->Type != '')
		        $value[] = ' m:type="' . $azureValue->Type . '"';
		    if (is_null($azureValue->Value))
		        $value[] = ' m:null="true"'; 
		    $value[] = '>';
		    if (!is_null($azureValue->Value))
		        $value[] = $azureValue->Value;
		    $value[] = '</d:' . $azureValue->Name . '>';
		    $azureRepresentation[] = implode('', $value);
		}
		return implode('', $azureRepresentation);
	}
}
