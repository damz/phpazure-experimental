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
 * @license    http://todo     name_todo
 * @version    $Id: Blob.php 24241 2009-07-22 09:43:13Z unknown $
 */

/**
 * @see Microsoft_Azure_SharedKeyCredentials
 */
require_once 'Microsoft/Azure/SharedKeyCredentials.php';

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
 * Microsoft_Azure_Storage_QueueInstance
 */
require_once 'Microsoft/Azure/Storage/QueueInstance.php';

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
class Microsoft_Azure_Storage_Queue extends Microsoft_Azure_Storage
{
	/**
	 * Creates a new Microsoft_Azure_Storage_Queue instance
	 *
	 * @param string $host Storage host name
	 * @param string $accountName Account name for Windows Azure
	 * @param string $accountKey Account key for Windows Azure
	 * @param boolean $usePathStyleUri Use path-style URI's
	 * @param Microsoft_Azure_RetryPolicy $retryPolicy Retry policy to use when making requests
	 */
	public function __construct($host = Microsoft_Azure_Storage::URL_DEV_BLOB, $accountName = Microsoft_Azure_SharedKeyCredentials::DEVSTORE_ACCOUNT, $accountKey = Microsoft_Azure_SharedKeyCredentials::DEVSTORE_KEY, $usePathStyleUri = false, Microsoft_Azure_RetryPolicy $retryPolicy = null)
	{
		parent::__construct($host, $accountName, $accountKey, $usePathStyleUri, $retryPolicy);
	}
	
	/**
	 * Create queue
	 *
	 * @param string $queueName Queue name
	 * @param array  $metadata  Key/value pairs of meta data
	 * @return object Queue properties
	 * @throws Microsoft_Azure_Exception
	 */
	public function createQueue($queueName = '', $metadata = array())
	{
		if ($queueName === '')
			throw new Microsoft_Azure_Exception('Queue name is not specified.');
		if (!self::isValidQueueName($queueName))
		    throw new Microsoft_Azure_Exception('Queue name does not adhere to queue naming conventions. See http://msdn.microsoft.com/en-us/library/dd179349.aspx for more information.');
			
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($queueName, '', Microsoft_Http_Transport::VERB_PUT, $headers);			
		if ($response->isSuccessful())
		{
		    return new Microsoft_Azure_Storage_QueueInstance(
		        $queueName,
		        $metadata
		    );
		}
		else
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Get queue
	 * 
	 * @param string $queueName  Queue name
	 * @return Microsoft_Azure_Storage_QueueInstance
	 * @throws Microsoft_Azure_Exception
	 */
	public function getQueue($queueName = '')
	{
		if ($queueName === '')
			throw new Microsoft_Azure_Exception('Queue name is not specified.');
		if (!self::isValidQueueName($queueName))
		    throw new Microsoft_Azure_Exception('Queue name does not adhere to queue naming conventions. See http://msdn.microsoft.com/en-us/library/dd179349.aspx for more information.');
		    
		// Perform request
		$response = $this->performRequest($queueName, '?comp=metadata', Microsoft_Http_Transport::VERB_GET);	
		if ($response->isSuccessful())
		{
		    // Parse metadata
		    $metadata = array();
		    foreach ($response->getHeaders() as $key => $value)
		    {
		        if (substr(strtolower($key), 0, 10) == "x-ms-meta-")
		        {
		            $metadata[str_replace("x-ms-meta-", '', strtolower($key))] = $value;
		        }
		    }

		    // Return queue
		    $queue = new Microsoft_Azure_Storage_QueueInstance(
		        $queueName,
		        $metadata
		    );
		    $queue->ApproximateMessageCount = intval($response->getHeader('x-ms-approximate-message-count'));
		    return $queue;
		}
		else
		{
		    throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Get queue metadata
	 * 
	 * @param string $queueName  Queue name
	 * @return array Key/value pairs of meta data
	 * @throws Microsoft_Azure_Exception
	 */
	public function getQueueMetadata($queueName = '')
	{
		if ($queueName === '')
			throw new Microsoft_Azure_Exception('Queue name is not specified.');
		if (!self::isValidQueueName($queueName))
		    throw new Microsoft_Azure_Exception('Queue name does not adhere to queue naming conventions. See http://msdn.microsoft.com/en-us/library/dd179349.aspx for more information.');
			
	    return $this->getQueue($queueName)->Metadata;
	}
	
	/**
	 * Set queue metadata
	 * 
	 * Calling the Set Queue Metadata operation overwrites all existing metadata that is associated with the queue. It's not possible to modify an individual name/value pair.
	 *
	 * @param string $queueName  Queue name
	 * @param array  $metadata       Key/value pairs of meta data
	 * @throws Microsoft_Azure_Exception
	 */
	public function setQueueMetadata($queueName = '', $metadata = array())
	{
		if ($queueName === '')
			throw new Microsoft_Azure_Exception('Queue name is not specified.');
		if (!self::isValidQueueName($queueName))
		    throw new Microsoft_Azure_Exception('Queue name does not adhere to queue naming conventions. See http://msdn.microsoft.com/en-us/library/dd179349.aspx for more information.');
		if (count($metadata) == 0)
		    return;
		    
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($queueName, '?comp=metadata', Microsoft_Http_Transport::VERB_PUT, $headers);

		if (!$response->isSuccessful())
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * Delete queue
	 *
	 * @param string $queueName Queue name
	 * @throws Microsoft_Azure_Exception
	 */
	public function deleteQueue($queueName = '')
	{
		if ($queueName === '')
			throw new Microsoft_Azure_Exception('Queue name is not specified.');
		if (!self::isValidQueueName($queueName))
		    throw new Microsoft_Azure_Exception('Queue name does not adhere to queue naming conventions. See http://msdn.microsoft.com/en-us/library/dd179349.aspx for more information.');
			
		// Perform request
		$response = $this->performRequest($queueName, '', Microsoft_Http_Transport::VERB_DELETE);
		if (!$response->isSuccessful())
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * List queues
	 *
	 * @param string $prefix     Optional. Filters the results to return only queues whose name begins with the specified prefix.
	 * @param int    $maxResults Optional. Specifies the maximum number of queues to return per call to Azure storage. This does NOT affect list size returned by this function. (maximum: 5000)
	 * @param string $marker     Optional string value that identifies the portion of the list to be returned with the next list operation.
	 * @param int    $currentResultCount Current result count (internal use)
	 * @return array
	 * @throws Microsoft_Azure_Exception
	 */
	public function listQueues($prefix = null, $maxResults = null, $marker = null, $currentResultCount = 0)
	{
	    // Build query string
	    $queryString = '?comp=list';
	    if (!is_null($prefix))
	        $queryString .= '&prefix=' . $prefix;
	    if (!is_null($maxResults))
	        $queryString .= '&maxresults=' . $maxResults;
	    if (!is_null($marker))
	        $queryString .= '&marker=' . $marker;
	        
		// Perform request
		$response = $this->performRequest('', $queryString, Microsoft_Http_Transport::VERB_GET);	
		if ($response->isSuccessful())
		{
			$xmlQueues = $this->parseResponse($response)->Queues->Queue;
			$xmlMarker = (string)$this->parseResponse($response)->NextMarker;

			$queues = array();
			if (!is_null($xmlQueues))
			{
				for ($i = 0; $i < count($xmlQueues); $i++)
				{
					$queues[] = new Microsoft_Azure_Storage_QueueInstance(
						(string)$xmlQueues[$i]->QueueName
					);
				}
			}
			$currentResultCount = $currentResultCount + count($queues);
			if (!is_null($maxResults) && $currentResultCount < $maxResults)
			{
    			if (!is_null($xmlMarker) && $xmlMarker != '')
    			{
    			    $queues = array_merge($queues, $this->listQueues($prefix, $maxResults, $xmlMarker, $currentResultCount));
    			}
			}
			if (!is_null($maxResults) && count($queues) > $maxResults)
			    $queues = array_slice($queues, 0, $maxResults);
			    
			return $queues;
		}
		else 
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Is valid queue name?
	 *
	 * @param string $queueName Queue name
	 * @return boolean
	 */
    public static function isValidQueueName($queueName = '')
    {
        if (!ereg("^[a-z0-9][a-z0-9-]*", $queueName))
            return false;
    
        if (strpos($queueName, '--') !== false)
            return false;
    
        if (strtolower($queueName) != $queueName)
            return false;
    
        if (strlen($queueName) < 3 || strlen($queueName) > 63)
            return false;
    
        return true;
    }
}
