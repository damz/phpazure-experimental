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
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://todo     name_todo
 * @version    $Id: SharedKeyAuthentication.php 11747 2008-10-08 18:33:58Z norm2782 $
 */

/**
 * @see Microsoft_Http_Transport
 */
require_once 'Microsoft/Http/Transport.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Azure
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://todo     name_todo
 */ 
class Microsoft_Azure_SharedKeyCredentials
{
	/**
	 * Development storage account and key
	 */
	const DEVSTORE_ACCOUNT       = "devstoreaccount1";
	const DEVSTORE_KEY           = "Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==";
	
	/**
	 * HTTP header prefixes
	 */
	const PREFIX_PROPERTIES      = "x-ms-prop-";
	const PREFIX_METADATA        = "x-ms-meta-";
	const PREFIX_STORAGE_HEADER  = "x-ms-";

	/**
	 * Account name for Windows Azure
	 *
	 * @var string
	 */
	protected $_accountName = '';
	
	/**
	 * Account key for Windows Azure
	 *
	 * @var string
	 */
	protected $_accountKey = '';
	
	/**
	 * Use path-style URI's
	 *
	 * @var boolean
	 */
	protected $_usePathStyleUri = false;
	
	/**
	 * Creates a new Microsoft_Azure_SharedKeyCredentials instance
	 *
	 * @param string $accountName Account name for Windows Azure
	 * @param string $accountKey Account key for Windows Azure
	 * @param boolean $usePathStyleUri Use path-style URI's
	 */
	public function __construct($accountName = self::DEVSTORE_ACCOUNT, $accountKey = self::DEVSTORE_KEY, $usePathStyleUri = false)
	{
		$this->_accountName = $accountName;
		$this->_accountKey = base64_decode($accountKey);
		$this->_usePathStyleUri = $usePathStyleUri;
	}
	
	/**
	 * Sign request with credentials
	 *
	 * @param string $httpVerb HTTP verb the request will use
	 * @param string $path Path for the request
	 * @param string $queryString Query string for the request
	 * @param array $headers x-ms headers to add
	 * @param boolean $forTableStorage Is the request for table storage?
	 * @return array Array of headers
	 */
	public function signRequest($httpVerb = Microsoft_Http_Transport::VERB_GET, $path = '/', $queryString = '', $headers = null, $forTableStorage = false)
	{
		// TODO: Use $forTableStorage
		// http://github.com/sriramk/winazurestorage/blob/214010a2f8931bac9c96dfeb337d56fe084ca63b/winazurestorage.py

		// Determine path
		if ($this->_usePathStyleUri)
			$path = substr($path, strpos($path, '/'));

		// Determine query
		if (strlen($queryString) > 0 && strpos($queryString, '?') !== 0)
			$queryString = '?' . $queryString;	

		if (strpos($queryString, '&') !== false)
			$queryString = substr($queryString, 0, strpos($queryString, '&'));

		// Build canonicalized headers
		$canonicalizedHeaders = array();
		if (!is_null($headers))
		{
			foreach ($headers as $header => $value) {
				if (is_bool($value))
					$value = $value === true ? 'True' : 'False';

				$headers[$header] = $value;
				if (substr($header, 0, strlen(self::PREFIX_STORAGE_HEADER)) == self::PREFIX_STORAGE_HEADER)
				    $canonicalizedHeaders[] = $header . ':' . $value;
			}
		}

		// Build canonicalized resource string
		$canonicalizedResource  = '/' . $this->_accountName;
		if ($this->_usePathStyleUri)
			$canonicalizedResource .= '/' . $this->_accountName;
		$canonicalizedResource .= $path;
		if ($queryString !== '')
		    $canonicalizedResource .= '/' . $queryString;

		// Request date
		$requestDate = '';
		if (isset($headers[self::PREFIX_STORAGE_HEADER . 'date']))
		{
		    $requestDate = $headers[self::PREFIX_STORAGE_HEADER . 'date'];
		}
		else 
		{
		    $requestDate = gmdate('D, d M Y H:i:s', time()) . ' GMT'; // RFC 1123
		}

		// Create string to sign   
		$stringToSign = array();
		$stringToSign[] = strtoupper($httpVerb); 	// VERB
    	$stringToSign[] = "";						// Content-MD5
    	$stringToSign[] = "";						// Content-Type
    	$stringToSign[] = "";
    	$stringToSign[] = self::PREFIX_STORAGE_HEADER . 'date:' . $requestDate; // Date
    	
    	if (count($canonicalizedHeaders) > 0)
    		$stringToSign[] = implode("\n", $canonicalizedHeaders); // Canonicalized headers
    		
    	$stringToSign[] = $canonicalizedResource;		 			// Canonicalized resource
    	$stringToSign = implode("\n", $stringToSign);

    	$signString = base64_encode(hash_hmac('sha256', $stringToSign, $this->_accountKey, true));

    	// Sign request
    	$headers[self::PREFIX_STORAGE_HEADER . 'date'] = $requestDate;
    	$headers['Authorization'] = 'SharedKey ' . $this->_accountName . ':' . $signString;
    	
    	// Return headers
    	return $headers;
	}
}
