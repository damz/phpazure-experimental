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
 * @version    $Id: Storage.php 21617 2009-06-12 10:46:31Z unknown $
 */

/**
 * @see Microsoft_Azure_Storage
 */
require_once 'Microsoft/Azure/Storage.php';

/**
 * @see Microsoft_Azure_Exception
 */
require_once 'Microsoft/Azure/Exception.php';

/**
 * @see Microsoft_Azure_Storage_Batch
 */
require_once 'Microsoft/Azure/Storage/Batch.php';

/**
 * @see Microsoft_Http_Transport
 */
require_once 'Microsoft/Http/Transport.php';

/**
 * @see Microsoft_Http_Response
 */
require_once 'Microsoft/Http/Response.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
abstract class Microsoft_Azure_Storage_BatchStorage extends Microsoft_Azure_Storage
{	
    /**
     * Current batch
     * 
     * @var Microsoft_Azure_Storage_Batch
     */
    protected $_currentBatch = null;
    
    /**
     * Set current batch
     * 
     * @param Microsoft_Azure_Storage_Batch $batch Current batch
     * @throws Microsoft_Azure_Exception
     */
    public function setCurrentBatch(Microsoft_Azure_Storage_Batch $batch = null)
    {
        if (!is_null($batch) && $this->isInBatch())
        {
            throw new Microsoft_Azure_Exception('Only one batch can be active at a time.');
        }
        $this->_currentBatch = $batch;
    }
    
    /**
     * Get current batch
     * 
     * @return Microsoft_Azure_Storage_Batch
     */
    public function getCurrentBatch()
    {
        return $this->_currentBatch;
    }
    
    /**
     * Is there a current batch?
     * 
     * @return boolean
     */
    public function isInBatch()
    {
        return !is_null($this->_currentBatch);
    }
    
    /**
     * Starts a new batch operation set
     * 
     * @return Microsoft_Azure_Storage_Batch
     * @throws Microsoft_Azure_Exception
     */
    public function startBatch()
    {
        return new Microsoft_Azure_Storage_Batch($this, $this->getBaseUrl());
    }
	
	/**
	 * Perform batch using Microsoft_Http_Transport channel, combining all batch operations into one request
	 *
	 * @param array $operations Operations in batch
	 * @param boolean $forTableStorage Is the request for table storage?
	 * @param boolean $isSingleSelect Is the request a single select statement?
	 * @return Microsoft_Http_Response
	 */
	public function performBatch($operations = array(), $forTableStorage = false, $isSingleSelect = false)
	{
	    // Generate boundaries
	    $batchBoundary = 'batch_' . md5(time() . microtime());
	    $changesetBoundary = 'changeset_' . md5(time() . microtime());
	    
	    // Set headers
	    $headers = array();
	    
		// Add version header
		$headers['x-ms-version'] = self::API_VERSION;
		
		// Add content-type header
		$headers['Content-Type'] = 'multipart/mixed; boundary=' . $batchBoundary;

		// Set path and query string
		$path           = '/$batch';
		$queryString    = '';
		
		// Set verb
		$httpVerb = Microsoft_Http_Transport::VERB_POST;
		
		// Generate raw data
    	$rawData = '';
    		
		// Single select?
		if ($isSingleSelect)
		{
		    $operation = $operations[0];
		    $rawData .= '--' . $batchBoundary . "\n";
            $rawData .= 'Content-Type: application/http' . "\n";
            $rawData .= 'Content-Transfer-Encoding: binary' . "\n\n";
            $rawData .= $operation; 
            $rawData .= '--' . $batchBoundary . '--';
		} 
		else 
		{
    		$rawData .= '--' . $batchBoundary . "\n";
    		$rawData .= 'Content-Type: multipart/mixed; boundary=' . $changesetBoundary . "\n\n";
    		
        		// Add operations
        		foreach ($operations as $operation)
        		{
                    $rawData .= '--' . $changesetBoundary . "\n";
                	$rawData .= 'Content-Type: application/http' . "\n";
                	$rawData .= 'Content-Transfer-Encoding: binary' . "\n\n";
                	$rawData .= $operation;
        		}
        		$rawData .= '--' . $changesetBoundary . '--' . "\n";
    		    		    
    		$rawData .= '--' . $batchBoundary . '--';
		}

		// Generate URL and sign request
		$requestUrl     = $this->getBaseUrl() . $path . $queryString;
		$requestHeaders = $this->_credentials->signRequest($httpVerb, $path, $queryString, $headers, $forTableStorage);

		$requestClient  = Microsoft_Http_Transport::createChannel();
		if ($this->_useProxy)
		{
		    $requestClient->setProxy($this->_useProxy, $this->_proxyUrl, $this->_proxyPort, $this->_proxyCredentials);
		}
		$response = $this->_retryPolicy->execute(
		    array($requestClient, 'request'),
		    array($httpVerb, $requestUrl, array(), $requestHeaders, $rawData)
		);
		
		$requestClient = null;
		unset($requestClient);

		return $response;
	}
}
