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
 * @version    $Id$
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
 * @see Microsoft_Azure_Storage_BlobContainer
 */
require_once 'Microsoft/Azure/Storage/BlobContainer.php';

/**
 * @see Microsoft_Azure_Storage_BlobInstance
 */
require_once 'Microsoft/Azure/Storage/BlobInstance.php';

/**
 * @see Microsoft_Azure_Exception
 */
require_once 'Microsoft/Azure/Exception.php';


/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://todo     name_todo
 */
class Microsoft_Azure_Storage_Blob extends Microsoft_Azure_Storage
{
	/**
	 * ACL - Private access
	 */
	const ACL_PRIVATE = false;
	
	/**
	 * ACL - Public access
	 */
	const ACL_PUBLIC = true;
	
	/**
	 * Maximal blob size (in bytes)
	 */
	const MAX_BLOB_SIZE = 67108864;

	/**
	 * Maximal blob transfer size (in bytes)
	 */
	const MAX_BLOB_TRANSFER_SIZE = 4194304;
	
	/**
	 * Creates a new Microsoft_Azure_Storage_Blob instance
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
	 * Create container
	 *
	 * @param string $containerName Container name
	 * @param array  $metadata      Key/value pairs of meta data
	 * @return object Container properties
	 * @throws Microsoft_Azure_Exception
	 */
	public function createContainer($containerName = '', $metadata = array())
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
			
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($containerName, '', Microsoft_Http_Transport::VERB_PUT, $headers);			
		if ($response->isSuccessful())
		{
		    return new Microsoft_Azure_Storage_BlobContainer(
		        $containerName,
		        $response->getHeader('Etag'),
		        $response->getHeader('Last-modified'),
		        $metadata
		    );
		}
		else
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Get container ACL
	 *
	 * @param string $containerName Container name
	 * @return bool Acl, to be compared with Microsoft_Azure_Storage_Blob::ACL_*
	 * @throws Microsoft_Azure_Exception
	 */
	public function getContainerAcl($containerName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');

		// Perform request
		$response = $this->performRequest($containerName, '?comp=acl', Microsoft_Http_Transport::VERB_GET);
		if ($response->isSuccessful())
			return $response->getHeader('x-ms-prop-publicaccess') == 'True';
		else
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * Set container ACL
	 *
	 * @param string $containerName Container name
	 * @param bool $acl Microsoft_Azure_Storage_Blob::ACL_*
	 * @throws Microsoft_Azure_Exception
	 */
	public function setContainerAcl($containerName = '', $acl = self::ACL_PRIVATE)
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');

		// Perform request
		$response = $this->performRequest($containerName, '?comp=acl', Microsoft_Http_Transport::VERB_PUT, array('x-ms-prop-publicaccess' => $acl));
		if (!$response->isSuccessful())
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * Get container
	 * 
	 * @param string $containerName  Container name
	 * @return Microsoft_Azure_Storage_BlobContainer
	 * @throws Microsoft_Azure_Exception
	 */
	public function getContainer($containerName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		    
		// Perform request
		$response = $this->performRequest($containerName, '', Microsoft_Http_Transport::VERB_GET);	
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

		    // Return container
		    return new Microsoft_Azure_Storage_BlobContainer(
		        $containerName,
		        $response->getHeader('Etag'),
		        $response->getHeader('Last-modified'),
		        $metadata
		    );
		}
		else
		{
		    throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Get container metadata
	 * 
	 * @param string $containerName  Container name
	 * @return array Key/value pairs of meta data
	 * @throws Microsoft_Azure_Exception
	 */
	public function getContainerMetadata($containerName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		
	    return $this->getContainer($containerName)->Metadata;
	}
	
	/**
	 * Set container metadata
	 * 
	 * Calling the Set Container Metadata operation overwrites all existing metadata that is associated with the container. It's not possible to modify an individual name/value pair.
	 *
	 * @param string $containerName  Container name
	 * @param array  $metadata       Key/value pairs of meta data
	 * @throws Microsoft_Azure_Exception
	 */
	public function setContainerMetadata($containerName = '', $metadata = array())
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if (count($metadata) == 0)
		    return;
		    
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($containerName, '?comp=metadata', Microsoft_Http_Transport::VERB_PUT, $headers);

		if (!$response->isSuccessful())
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * Delete container
	 *
	 * @param string $containerName Container name
	 * @throws Microsoft_Azure_Exception
	 */
	public function deleteContainer($containerName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
			
		// Perform request
		$response = $this->performRequest($containerName, '', Microsoft_Http_Transport::VERB_DELETE);
		if (!$response->isSuccessful())
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * List containers
	 *
	 * @param string $prefix     Optional. Filters the results to return only containers whose name begins with the specified prefix.
	 * @param int    $maxResults Optional. Specifies the maximum number of containers to return per call to Azure storage. This does NOT affect list size returned by this function. (maximum: 5000)
	 * @param string $marker     Optional string value that identifies the portion of the list to be returned with the next list operation.
	 * @return array
	 * @throws Microsoft_Azure_Exception
	 */
	public function listContainers($prefix = null, $maxResults = null, $marker = null)
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
			$xmlContainers = $this->parseResponse($response)->Containers->Container;
			$xmlMarker = (string)$this->parseResponse($response)->NextMarker;

			$containers = array();
			if (!is_null($xmlContainers))
			{
				for ($i = 0; $i < count($xmlContainers); $i++)
				{
					$containers[] = new Microsoft_Azure_Storage_BlobContainer(
						(string)$xmlContainers[$i]->Name,
						(string)$xmlContainers[$i]->Etag,
						(string)$xmlContainers[$i]->LastModified
					);
				}
			}
			if (!is_null($xmlMarker) && $xmlMarker != '')
			{
			    $containers = array_merge($containers, $this->listContainers($prefix, $maxResults, $xmlMarker));
			}
			
			return $containers;
		}
		else 
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Put blob
	 *
	 * @param string $containerName Container name
	 * @param string $blobName Blob name
	 * @param string $localFileName Local file name to be uploaded
	 * @param array  $metadata      Key/value pairs of meta data
	 * @return object Partial blob properties
	 * @throws Microsoft_Azure_Exception
	 */
	public function putBlob($containerName = '', $blobName = '', $localFileName = '', $metadata = array())
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if ($blobName === '')
			throw new Microsoft_Azure_Exception('Blob name is not specified.');
		if ($localFileName === '')
			throw new Microsoft_Azure_Exception('Local file name is not specified.');
		if (!file_exists($localFileName))
			throw new Microsoft_Azure_Exception('Local file not found.');
			
		// Check file size
		if (filesize($localFileName) >= self::MAX_BLOB_SIZE)
			return $this->putLargeBlob($containerName, $blobName, $localFileName, $metadata);

		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// File contents
		$fileContents = file_get_contents($localFileName);
		
		// Perform request
		$response = $this->performRequest($containerName . '/' . $blobName, '', Microsoft_Http_Transport::VERB_PUT, $headers, false, $fileContents);
		if ($response->isSuccessful())
		{
			return new Microsoft_Azure_Storage_BlobInstance(
				$containerName,
				$blobName,
				$response->getHeader('Etag'),
				$response->getHeader('Last-modified'),
				$this->getBaseUrl() . '/' . $containerName . '/' . $blobName,
				strlen($fileContents),
				'',
				'',
				'',
				false,
		        $metadata
			);
		}
		else
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Put large blob (> 64 MB)
	 *
	 * @param string $containerName Container name
	 * @param string $blobName Blob name
	 * @param string $localFileName Local file name to be uploaded
	 * @param array  $metadata      Key/value pairs of meta data
	 * @return object Partial blob properties
	 * @throws Microsoft_Azure_Exception
	 */
	public function putLargeBlob($containerName = '', $blobName = '', $localFileName = '', $metadata = array())
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if ($blobName === '')
			throw new Microsoft_Azure_Exception('Blob name is not specified.');
		if ($localFileName === '')
			throw new Microsoft_Azure_Exception('Local file name is not specified.');
		if (!file_exists($localFileName))
			throw new Microsoft_Azure_Exception('Local file not found.');
			
		// Check file size
		if (filesize($localFileName) < self::MAX_BLOB_SIZE)
			return $this->putBlob($containerName, $blobName, $localFileName, $metadata);
			
		// Determine number of parts
		$numberOfParts = ceil( filesize($localFileName) / self::MAX_BLOB_TRANSFER_SIZE );
		
		// Generate block id's
		$blockIdentifiers = array();
		for ($i = 0; $i < $numberOfParts; $i++)
		{
			$blockIdentifiers[] = $this->generateBlockId($i);
		}
		
		// Open file
		$fp = fopen($localFileName, 'r');
		if ($fp === false)
			throw new Microsoft_Azure_Exception('Could not open local file.');
			
		// Upload parts
		for ($i = 0; $i < $numberOfParts; $i++)
		{
			// Seek position in file
			fseek($fp, $i * self::MAX_BLOB_TRANSFER_SIZE);
			
			// Read contents
			$fileContents = fread($fp, self::MAX_BLOB_TRANSFER_SIZE);

			// Upload
			$response = $this->performRequest($containerName . '/' . $blobName, '?comp=block&blockid=' . base64_encode($blockIdentifiers[$i]), Microsoft_Http_Transport::VERB_PUT, null, false, $fileContents);
			if (!$response->isSuccessful())
			{
				throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
			}
		}
		
		// Close file
		fclose($fp);
		
		// Generate block list
		$blockList = '';
		foreach ($blockIdentifiers as $block)
		{
			$blockList .= '  <Block>' . base64_encode($block) . '</Block>' . "\n";
		}
		
		// Generate block list request
		$fileContents = utf8_encode(implode("\n", array(
			'<?xml version="1.0" encoding="utf-8"?>',
			'<BlockList>',
			$blockList,
			'</BlockList>'
		)));
		
	    // Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}

		// Perform request
		$response = $this->performRequest($containerName . '/' . $blobName, '?comp=blocklist', Microsoft_Http_Transport::VERB_PUT, $headers, false, $fileContents);
		if ($response->isSuccessful())
		{
			return new Microsoft_Azure_Storage_BlobInstance(
				$containerName,
				$blobName,
				$response->getHeader('Etag'),
				$response->getHeader('Last-modified'),
				$this->getBaseUrl() . '/' . $containerName . '/' . $blobName,
				filesize($localFileName),
				'',
				'',
				'',
				false,
		        $metadata
			);
		}
		else
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Get blob
	 *
	 * @param string $containerName Container name
	 * @param string $blobName Blob name
	 * @param string $localFileName Local file name to store downloaded blob
	 * @throws Microsoft_Azure_Exception
	 */
	public function getBlob($containerName = '', $blobName = '', $localFileName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if ($blobName === '')
			throw new Microsoft_Azure_Exception('Blob name is not specified.');
		if ($localFileName === '')
			throw new Microsoft_Azure_Exception('Local file name is not specified.');
			
		// Perform request
		$response = $this->performRequest($containerName . '/' . $blobName, '', Microsoft_Http_Transport::VERB_GET);
		if ($response->isSuccessful())
			file_put_contents($localFileName, $response->getBody());
		else
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * Get container
	 * 
	 * @param string $containerName  Container name
	 * @param string $blobName Blob name
	 * @return Microsoft_Azure_Storage_BlobInstance
	 * @throws Microsoft_Azure_Exception
	 */
	public function getBlobInstance($containerName = '', $blobName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if ($blobName === '')
			throw new Microsoft_Azure_Exception('Blob name is not specified.');
		    
		// Perform request
		$response = $this->performRequest($containerName . '/' . $blobName, '', Microsoft_Http_Transport::VERB_HEAD);
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

		    // Return blob
			return new Microsoft_Azure_Storage_BlobInstance(
				$containerName,
				$blobName,
				$response->getHeader('Etag'),
				$response->getHeader('Last-modified'),
				$this->getBaseUrl() . '/' . $containerName . '/' . $blobName,
				$response->getHeader('Content-Length'),
				$response->getHeader('Content-Type'),
				$response->getHeader('Content-Encoding'),
				$response->getHeader('Content-Language'),
				false,
		        $metadata
			);
		}
		else
		{
		    throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Get blob metadata
	 * 
	 * @param string $containerName  Container name
	 * @param string $blobName Blob name
	 * @return array Key/value pairs of meta data
	 * @throws Microsoft_Azure_Exception
	 */
	public function getBlobMetadata($containerName = '', $blobName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if ($blobName === '')
			throw new Microsoft_Azure_Exception('Blob name is not specified.');
		
	    return $this->getBlobInstance($containerName, $blobName)->Metadata;
	}
	
	/**
	 * Set blob metadata
	 * 
	 * Calling the Set Blob Metadata operation overwrites all existing metadata that is associated with the blob. It's not possible to modify an individual name/value pair.
	 *
	 * @param string $containerName  Container name
	 * @param string $blobName Blob name
	 * @param array  $metadata       Key/value pairs of meta data
	 * @throws Microsoft_Azure_Exception
	 */
	public function setBlobMetadata($containerName = '', $blobName = '', $metadata = array())
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if ($blobName === '')
			throw new Microsoft_Azure_Exception('Blob name is not specified.');
		if (count($metadata) == 0)
		    return;
		    
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($containerName . '/' . $blobName, '?comp=metadata', Microsoft_Http_Transport::VERB_PUT, $headers);

		if (!$response->isSuccessful())
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * Delete blob
	 *
	 * @param string $containerName Container name
	 * @param string $blobName Blob name
	 * @throws Microsoft_Azure_Exception
	 */
	public function deleteBlob($containerName = '', $blobName = '')
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
		if ($blobName === '')
			throw new Microsoft_Azure_Exception('Blob name is not specified.');
			
		// Perform request
		$response = $this->performRequest($containerName . '/' . $blobName, '', Microsoft_Http_Transport::VERB_DELETE);
		if (!$response->isSuccessful())
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
	}
	
	/**
	 * List blobs
	 *
	 * @param string $containerName Container name
	 * @param string $delimiter  Optional. Delimiter, i.e. '/', for specifying folder hierarchy
	 * @param int    $maxResults Optional. Specifies the maximum number of blobs to return per call to Azure storage. This does NOT affect list size returned by this function. (maximum: 5000)
	 * @param string $marker     Optional string value that identifies the portion of the list to be returned with the next list operation.
	 * @return array
	 * @throws Microsoft_Azure_Exception
	 */
	public function listBlobs($containerName = '', $delimiter = '', $maxResults = null, $marker = null)
	{
		if ($containerName === '')
			throw new Microsoft_Azure_Exception('Container name is not specified.');
			
	    // Build query string
	    $queryString = '?comp=list';
		if ($delimiter !== '')
			$queryString .= '&delimiter=' . $delimiter;
	    if (!is_null($maxResults))
	        $queryString .= '&maxresults=' . $maxResults;
	    if (!is_null($marker))
	        $queryString .= '&marker=' . $marker;

	    // Perform request
		$response = $this->performRequest($containerName, $queryString, Microsoft_Http_Transport::VERB_GET);
		if ($response->isSuccessful())
		{
		    // Return value
		    $blobs = array();
		    
			// Blobs
			$xmlBlobs = $this->parseResponse($response)->Blobs->Blob;
			if (!is_null($xmlBlobs))
			{
				for ($i = 0; $i < count($xmlBlobs); $i++)
				{
					$blobs[] = new Microsoft_Azure_Storage_BlobInstance(
						$containerName,
						(string)$xmlBlobs[$i]->Name,
						(string)$xmlBlobs[$i]->Etag,
						(string)$xmlBlobs[$i]->LastModified,
						(string)$xmlBlobs[$i]->Url,
						(string)$xmlBlobs[$i]->Size,
						(string)$xmlBlobs[$i]->ContentType,
						(string)$xmlBlobs[$i]->ContentEncoding,
						(string)$xmlBlobs[$i]->ContentLanguage,
						false
					);
				}
			}
			
			// Blob prefixes (folders)
			$xmlBlobs = $this->parseResponse($response)->Blobs->BlobPrefix;
			
			if (!is_null($xmlBlobs))
			{
				for ($i = 0; $i < count($xmlBlobs); $i++)
				{
					$blobs[] = new Microsoft_Azure_Storage_BlobInstance(
						$containerName,
						(string)$xmlBlobs[$i]->Name,
						'',
						'',
						'',
						0,
						'',
						'',
						'',
						true
					);
				}
			}
			
			// More blobs?
			$xmlMarker = (string)$this->parseResponse($response)->NextMarker;
			if (!is_null($xmlMarker) && $xmlMarker != '')
			{
			    $blobs = array_merge($blobs, $this->listBlobs($containerName, $delimiter, $maxResults, $marker));
			}
			
			return $blobs;
		}
		else 
		{
			throw new Microsoft_Azure_Exception((string)$this->parseResponse($response)->Message);
		}
	}
	
	/**
	 * Generate block id
	 * 
	 * @param int $part Block number
	 * @return string Windows Azure Blob Storage block number
	 */
	protected function generateBlockId($part = 0)
	{
		$returnValue = $part;
		while (strlen($returnValue) < 64)
		{
			$returnValue = '0' . $returnValue;
		}
		
		return $returnValue;
	}
}
