<?php
/**
 * Copyright (c) 2009 - 2010, RealDolmen
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
 * @package    Microsoft_WindowsAzure
 * @subpackage Management
 * @copyright  Copyright (c) 2009 - 2010, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: Storage.php 51671 2010-09-30 08:33:45Z unknown $
 */

/**
 * @see Microsoft_WindowsAzure_RetryPolicy_RetryPolicyAbstract
 */
require_once 'Microsoft/WindowsAzure/RetryPolicy/RetryPolicyAbstract.php';

/**
 * @see Microsoft_WindowsAzure_Exception
 */
require_once 'Microsoft/WindowsAzure/Exception.php';

/**
 * @see Microsoft_Http_Client
 */
require_once 'Microsoft/Http/Client.php';

/**
 * @see Microsoft_Http_Response
 */
require_once 'Microsoft/Http/Response.php';

/**
 * @see Microsoft_WindowsAzure_Management_OperationStatusInstance
 */
require_once 'Microsoft/WindowsAzure/Management/OperationStatusInstance.php';

/**
 * @see Microsoft_WindowsAzure_Management_StorageServiceInstance
 */
require_once 'Microsoft/WindowsAzure/Management/StorageServiceInstance.php';

/**
 * @see Microsoft_WindowsAzure_Management_DeploymentInstance
 */
require_once 'Microsoft/WindowsAzure/Management/DeploymentInstance.php';


/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage Management
 * @copyright  Copyright (c) 2009 - 2010, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_WindowsAzure_Management_Client
{
	/**
	 * Management service URL
	 */
	const URL_MANAGEMENT        = "https://management.core.windows.net";
	
	/**
	 * Operations
	 */
	const OP_OPERATIONS         = "operations";
	const OP_STORAGE_ACCOUNTS   = "services/storageservices";
	const OP_HOSTED_SERVICES    = "services/hostedservices";

	/**
	 * Current API version
	 * 
	 * @var string
	 */
	protected $_apiVersion = '2009-10-01';
	
	/**
	 * Subscription ID
	 *
	 * @var string
	 */
	protected $_subscriptionId = '';
	
	/**
	 * Management certificate path (.PEM)
	 *
	 * @var string
	 */
	protected $_certificatePath = '';
	
	/**
	 * Management certificate passphrase
	 *
	 * @var string
	 */
	protected $_certificatePassphrase = '';
	
	/**
	 * Microsoft_Http_Client channel used for communication with REST services
	 * 
	 * @var Microsoft_Http_Client
	 */
	protected $_httpClientChannel = null;	

	/**
	 * Microsoft_WindowsAzure_RetryPolicy_RetryPolicyAbstract instance
	 * 
	 * @var Microsoft_WindowsAzure_RetryPolicy_RetryPolicyAbstract
	 */
	protected $_retryPolicy = null;
	
	/**
	 * Returns the last request ID
	 * 
	 * @var string
	 */
	protected $_lastRequestId = null;
	
	/**
	 * Creates a new Microsoft_WindowsAzure_Management instance
	 * 
	 * @param string $subscriptionId Subscription ID
	 * @param string $certificatePath Management certificate path (.PEM)
	 * @param string $certificatePassphrase Management certificate passphrase
     * @param Microsoft_WindowsAzure_RetryPolicy_RetryPolicyAbstract $retryPolicy Retry policy to use when making requests
	 */
	public function __construct(
		$subscriptionId,
		$certificatePath,
		$certificatePassphrase,
		Microsoft_WindowsAzure_RetryPolicy_RetryPolicyAbstract $retryPolicy = null
	) {
		$this->_subscriptionId = $subscriptionId;
		$this->_certificatePath = $certificatePath;
		$this->_certificatePassphrase = $certificatePassphrase;
		
		$this->_retryPolicy = $retryPolicy;
		if (is_null($this->_retryPolicy)) {
		    $this->_retryPolicy = Microsoft_WindowsAzure_RetryPolicy_RetryPolicyAbstract::noRetry();
		}
		
		// Setup default Microsoft_Http_Client channel
		$options = array(
		    'adapter'       => 'Microsoft_Http_Client_Adapter_Socket',
		    'ssltransport'  => 'ssl',
			'sslcert'       => $this->_certificatePath,
			'sslpassphrase' => $this->_certificatePassphrase,
			'sslusecontext' => true,
		);
		if (function_exists('curl_init')) {
			// Set cURL options if cURL is used afterwards
			$options['curloptions'] = array(
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_TIMEOUT => 120,
			);
		}
		$this->_httpClientChannel = new Microsoft_Http_Client(null, $options);
	}
	
	/**
	 * Set the HTTP client channel to use
	 * 
	 * @param Microsoft_Http_Client_Adapter_Interface|string $adapterInstance Adapter instance or adapter class name.
	 */
	public function setHttpClientChannel($adapterInstance = 'Microsoft_Http_Client_Adapter_Socket')
	{
		$this->_httpClientChannel->setAdapter($adapterInstance);
	}
	
    /**
     * Retrieve HTTP client channel
     * 
     * @return Microsoft_Http_Client_Adapter_Interface
     */
    public function getHttpClientChannel()
    {
        return $this->_httpClientChannel;
    }
	
	/**
	 * Returns the Windows Azure subscription ID
	 * 
	 * @return string
	 */
	public function getSubscriptionId()
	{
		return $this->_subscriptionId;
	}
	
	/**
	 * Returns the last request ID.
	 * 
	 * @return string
	 */
	public function getLastRequestId()
	{
		return $this->_lastRequestId;
	}
	
	/**
	 * Get base URL for creating requests
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		return self::URL_MANAGEMENT . '/' . $this->_subscriptionId;
	}
	
	/**
	 * Perform request using Microsoft_Http_Client channel
	 *
	 * @param string $path Path
	 * @param string $queryString Query string
	 * @param string $httpVerb HTTP verb the request will use
	 * @param array $headers x-ms headers to add
	 * @param mixed $rawData Optional RAW HTTP data to be sent over the wire
	 * @return Microsoft_Http_Response
	 */
	protected function _performRequest(
		$path = '/',
		$queryString = '',
		$httpVerb = Microsoft_Http_Client::GET,
		$headers = array(),
		$rawData = null
	) {
	    // Clean path
		if (strpos($path, '/') !== 0) {
			$path = '/' . $path;
		}
			
		// Clean headers
		if (is_null($headers)) {
		    $headers = array();
		}
		
		// Ensure cUrl will also work correctly:
		//  - disable Content-Type if required
		//  - disable Expect: 100 Continue
		if (!isset($headers["Content-Type"])) {
			$headers["Content-Type"] = '';
		}
		//$headers["Expect"] = '';

		// Add version header
		$headers['x-ms-version'] = $this->_apiVersion;
		    
		// URL encoding
		$path           = self::urlencode($path);
		$queryString    = self::urlencode($queryString);

		// Generate URL and sign request
		$requestUrl     = $this->getBaseUrl() . $path . $queryString;
		$requestHeaders = $headers;

		// Prepare request 
		$this->_httpClientChannel->resetParameters(true);
		$this->_httpClientChannel->setUri($requestUrl);
		$this->_httpClientChannel->setHeaders($requestHeaders);
		$this->_httpClientChannel->setRawData($rawData);

		// Execute request
		$response = $this->_retryPolicy->execute(
		    array($this->_httpClientChannel, 'request'),
		    array($httpVerb)
		);
		
		// Store request id
		$this->_lastRequestId = $response->getHeader('x-ms-request-id');
		
		return $response;
	}
	
	/** 
	 * Parse result from Microsoft_Http_Response
	 *
	 * @param Microsoft_Http_Response $response Response from HTTP call
	 * @return object
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	protected function _parseResponse(Microsoft_Http_Response $response = null)
	{
		if (is_null($response)) {
			throw new Microsoft_WindowsAzure_Exception('Response should not be null.');
		}
		
        $xml = @simplexml_load_string($response->getBody());
        
        if ($xml !== false) {
            // Fetch all namespaces 
            $namespaces = array_merge($xml->getNamespaces(true), $xml->getDocNamespaces(true)); 
            
            // Register all namespace prefixes
            foreach ($namespaces as $prefix => $ns) { 
                if ($prefix != '') {
                    $xml->registerXPathNamespace($prefix, $ns);
                } 
            } 
        }
        
        return $xml;
	}
	
	/**
	 * URL encode function
	 * 
	 * @param  string $value Value to encode
	 * @return string        Encoded value
	 */
	public static function urlencode($value)
	{
	    return str_replace(' ', '%20', $value);
	}
	
    /**
     * Builds a query string from an array of elements
     * 
     * @param array     Array of elements
     * @return string   Assembled query string
     */
    public static function createQueryStringFromArray($queryString)
    {
    	return count($queryString) > 0 ? '?' . implode('&', $queryString) : '';
    }
    
	/**
	 * Get error message from Microsoft_Http_Response
	 *
	 * @param Microsoft_Http_Response $response Repsonse
	 * @param string $alternativeError Alternative error message
	 * @return string
	 */
	protected function _getErrorMessage(Microsoft_Http_Response $response, $alternativeError = 'Unknown error.')
	{
		$response = $this->_parseResponse($response);
		if ($response && $response->Message) {
			return (string)$response->Message;
		} else {
			return $alternativeError;
		}
	}
    
    /**
     * The Get Operation Status operation returns the status of the specified operation.
     * After calling an asynchronous operation, you can call Get Operation Status to
     * determine whether the operation has succeed, failed, or is still in progress.
     *
     * @param string $requestId The request ID. If omitted, the last request ID will be used.
     * @return Microsoft_WindowsAzure_Management_OperationStatusInstance
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function getOperationStatus($requestId = '')
    {
    	if ($requestId == '') {
    		$requestId = $this->getLastRequestId();
    	}
    	
    	$response = $this->_performRequest(self::OP_OPERATIONS . '/' . $requestId);

    	if ($response->isSuccessful()) {
			$xmlResponse = $this->_parseResponse($response);

			if (!is_null($xmlResponse)) {
				return new Microsoft_WindowsAzure_Management_OperationStatusInstance(
					(string)$xmlResponse->ID,
					(string)$xmlResponse->Status,
					($xmlResponse->Error ? (string)$xmlResponse->Error->Code : ''),
					($xmlResponse->Error ? (string)$xmlResponse->Error->Message : '')
				);
			}
			return null;
		} else {
			throw new Microsoft_WindowsAzure_Management_Exception($this->_getErrorMessage($response, 'Resource could not be accessed.'));
		}
    }
    
    /**
     * The List Storage Accounts operation lists the storage accounts available under
     * the current subscription.
     *
     * @return array An array of Microsoft_WindowsAzure_Management_StorageServiceInstance
     */
    public function listStorageAccounts()
    {
    	$response = $this->_performRequest(self::OP_STORAGE_ACCOUNTS);

    	if ($response->isSuccessful()) {
			$result = $this->_parseResponse($response);
		    if (count($result->StorageService) > 1) {
    		    $xmlServices = $result->StorageService;
    		} else {
    		    $xmlServices = array($result->StorageService);
    		}
    		
			$services = array();
			if (!is_null($xmlServices)) {				
				for ($i = 0; $i < count($xmlServices); $i++) {
					$services[] = new Microsoft_WindowsAzure_Management_StorageServiceInstance(
					    (string)$xmlServices[$i]->Url,
					    (string)$xmlServices[$i]->ServiceName
					);
				}
			}
			return $services;
		} else {
			throw new Microsoft_WindowsAzure_Management_Exception($this->_getErrorMessage($response, 'Resource could not be accessed.'));
		}
    }
    
    /**
     * The Get Storage Account Properties operation returns the system properties for the
     * specified storage account. These properties include: the address, description, and 
     * label of the storage account; and the name of the affinity group to which the service
     * belongs, or its geo-location if it is not part of an affinity group.
     *
     * @param string $serviceName The name of your service.
     * @return Microsoft_WindowsAzure_Management_StorageServiceInstance
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function getStorageAccountProperties($serviceName)
    {
    	if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
    	
    	$response = $this->_performRequest(self::OP_STORAGE_ACCOUNTS . '/' . $serviceName);

    	if ($response->isSuccessful()) {
			$xmlService = $this->_parseResponse($response);

			if (!is_null($xmlService)) {
				return new Microsoft_WindowsAzure_Management_StorageServiceInstance(
					(string)$xmlService->Url,
					(string)$xmlService->ServiceName,
					(string)$xmlService->StorageServiceProperties->Description,
					(string)$xmlService->StorageServiceProperties->AffinityGroup,
					(string)$xmlService->StorageServiceProperties->Location,
					(string)$xmlService->StorageServiceProperties->Label
				);
			}
			return null;
		} else {
			throw new Microsoft_WindowsAzure_Management_Exception($this->_getErrorMessage($response, 'Resource could not be accessed.'));
		}
    }
    
    /**
     * The Get Storage Keys operation returns the primary
     * and secondary access keys for the specified storage account.
     *
     * @param string $serviceName The name of your service.
     * @return array An array of strings
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function getStorageAccountKeys($serviceName)
    {
    	if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
    	
    	$response = $this->_performRequest(self::OP_STORAGE_ACCOUNTS . '/' . $serviceName . '/keys');

    	if ($response->isSuccessful()) {
			$xmlService = $this->_parseResponse($response);

			if (!is_null($xmlService)) {
				return array(
					(string)$xmlService->StorageServiceKeys->Primary,
					(string)$xmlService->StorageServiceKeys->Secondary
				);
			}
			return array();
		} else {
			throw new Microsoft_WindowsAzure_Management_Exception($this->_getErrorMessage($response, 'Resource could not be accessed.'));
		}
    }
    
    /**
     * The Regenerate Keys operation regenerates the primary
     * or secondary access key for the specified storage account.
     *
     * @param string $serviceName The name of your service.
     * @param string $key		  The key to regenerate (primary or secondary)
     * @return array An array of strings
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function regenerateStorageAccountKey($serviceName, $key = 'primary')
    {
    	if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
    	$key = strtolower($key);
    	if ($key != 'primary' && $key != 'secondary') {
    		throw new Microsoft_WindowsAzure_Management_Exception('Key identifier should be primary|secondary.');
    	}
    	
    	$response = $this->_performRequest(
    		self::OP_STORAGE_ACCOUNTS . '/' . $serviceName . '/keys', '?action=regenerate',
    		Microsoft_Http_Client::POST,
    		array('Content-Type' => 'application/xml'),
    		'<?xml version="1.0" encoding="utf-8"?>
             <RegenerateKeys xmlns="http://schemas.microsoft.com/windowsazure">
               <KeyType>' . ucfirst($key) . '</KeyType>
             </RegenerateKeys>');

    	if ($response->isSuccessful()) {
			$xmlService = $this->_parseResponse($response);

			if (!is_null($xmlService)) {
				return array(
					(string)$xmlService->StorageServiceKeys->Primary,
					(string)$xmlService->StorageServiceKeys->Secondary
				);
			}
			return array();
		} else {
			throw new Microsoft_WindowsAzure_Management_Exception($this->_getErrorMessage($response, 'Resource could not be accessed.'));
		}
    }
    
    /**
     * The Get Deployment operation returns configuration information, status,
     * and system properties for the specified deployment.
     * 
     * @param string $serviceName		The service name
     * @param string $deploymentSlot	The deployment slot (production or staging)
     * @return Microsoft_WindowsAzure_Management_DeploymentInstance
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function getDeploymentBySlot($serviceName, $deploymentSlot)
    {
        if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
    	$deploymentSlot = strtolower($deploymentSlot);
    	if ($deploymentSlot != 'production' && $deploymentSlot != 'staging') {
    		throw new Microsoft_WindowsAzure_Management_Exception('Deployment slot should be production|staging.');
    	}
    	
    	$operationUrl = self::OP_HOSTED_SERVICES . '/' . $serviceName . '/deploymentslots/' . $deploymentSlot;
    	return $this->_getDeployment($operationUrl);
    }
    
    /**
     * The Get Deployment operation returns configuration information, status,
     * and system properties for the specified deployment.
     * 
     * @param string $serviceName		The service name
     * @param string $deploymentName	The deployment name
     * @return Microsoft_WindowsAzure_Management_DeploymentInstance
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function getDeploymentByName($serviceName, $deploymentName)
    {
        if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
        if ($deploymentName == '' || is_null($deploymentName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Deployment name should be specified.');
    	}
    	
    	$operationUrl = self::OP_HOSTED_SERVICES . '/' . $serviceName . '/deployments/' . $deploymentName;
    	return $this->_getDeployment($operationUrl);
    }
    
    /**
     * The Get Deployment operation returns configuration information, status,
     * and system properties for the specified deployment.
     * 
     * @param string $operationUrl		The operation url
     * @return Microsoft_WindowsAzure_Management_DeploymentInstance
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    protected function _getDeployment($operationUrl)
    {
        $response = $this->_performRequest($operationUrl);

    	if ($response->isSuccessful()) {
			$xmlService = $this->_parseResponse($response);

			if (!is_null($xmlService)) {
				$returnValue = new Microsoft_WindowsAzure_Management_DeploymentInstance(
					(string)$xmlService->Name,
					(string)$xmlService->DeploymentSlot,
					(string)$xmlService->PrivateID,
					(string)$xmlService->Label,
					(string)$xmlService->Url,
					(string)$xmlService->Configuration,
					(string)$xmlService->Status,
					(string)$xmlService->UpgradeStatus,
					(string)$xmlService->UpgradeType,
					(string)$xmlService->CurrentUpgradeDomainState,
					(string)$xmlService->CurrentUpgradeDomain,
					(string)$xmlService->UpgradeDomainCount
				);
				
				// Append role instances
				$xmlRoleInstances = $xmlService->RoleInstanceList->RoleInstance;
			    if (count($xmlService->RoleInstanceList->RoleInstance) == 1) {
	    		    $xmlRoleInstances = array($xmlService->RoleInstanceList->RoleInstance);
	    		}
	    		
				$roleInstances = array();
				if (!is_null($xmlRoleInstances)) {				
					for ($i = 0; $i < count($xmlRoleInstances); $i++) {
						$roleInstances[] = array(
						    (string)$xmlRoleInstances[$i]->RoleName,
						    (string)$xmlRoleInstances[$i]->InstanceName,
						    (string)$xmlRoleInstances[$i]->InstanceStatus
						);
					}
				}
			
				$returnValue->RoleInstanceList = $roleInstances;
				
				// Append roles
				$xmlRoles = $xmlService->RoleList->Role;
			    if (count($xmlService->RoleList->Role) == 1) {
	    		    $xmlRoles = array($xmlService->RoleList->Role);
	    		}
    		
				$roles = array();
				if (!is_null($xmlRoles)) {				
					for ($i = 0; $i < count($xmlRoles); $i++) {
						$roles[] = array(
						    (string)$xmlRoles[$i]->RoleName,
						    (string)$xmlRoles[$i]->InstanceName,
						    (string)$xmlRoles[$i]->InstanceStatus
						);
					}
				}
				$returnValue->RoleList = $roles;
				
				return $returnValue;
			}
			return null;
		} else {
			throw new Microsoft_WindowsAzure_Management_Exception($this->_getErrorMessage($response, 'Resource could not be accessed.'));
		}
    }
    
    /**
     * Updates a deployment's role instance count.
     * 
     * @param string $serviceName		The service name
     * @param string $deploymentSlot	The deployment slot (production or staging)
     * @param string|array $roleName	The role name
     * @param string|array $instanceCount The instance count
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
	public function setInstanceCountBySlot($serviceName, $deploymentSlot, $roleName, $instanceCount) {
	    if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
    	$deploymentSlot = strtolower($deploymentSlot);
    	if ($deploymentSlot != 'production' && $deploymentSlot != 'staging') {
    		throw new Microsoft_WindowsAzure_Management_Exception('Deployment slot should be production|staging.');
    	}
    	if ($roleName == '' || is_null($roleName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Role name name should be specified.');
    	}
    	
		// Get configuration
		$deployment = $this->getDeploymentBySlot($serviceName, $deploymentSlot);
		$configuration = $deployment->Configuration;
		$configuration = $this->_updateInstanceCountInConfiguration($roleName, $instanceCount, $configuration);
		
		// Update configuration
		$this->configureDeploymentBySlot($serviceName, $deploymentSlot, $configuration);		
	}
	
    /**
     * Updates a deployment's role instance count.
     * 
     * @param string $serviceName		The service name
     * @param string $deploymentSlot	The deployment slot (production or staging)
     * @param string|array $roleName	The role name
     * @param string|array $instanceCount The instance count
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function setInstanceCountByName($serviceName, $deploymentName, $roleName, $instanceCount)
    {
	    if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
        if ($deploymentName == '' || is_null($deploymentName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Deployment name should be specified.');
    	}
    	if ($roleName == '' || is_null($roleName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Role name name should be specified.');
    	}
    	
		// Get configuration
		$deployment = $this->getDeploymentByName($serviceName, $deploymentName);
		$configuration = $deployment->Configuration;
		$configuration = $this->_updateInstanceCountInConfiguration($roleName, $instanceCount, $configuration);
		
		// Update configuration
		$this->configureDeploymentByName($serviceName, $deploymentName, $configuration);
    }
	
    /**
     * Updates instance count in configuration XML.
     * 
     * @param string|array $roleName			The role name
     * @param string|array $instanceCount		The instance count
     * @param string $configuration             XML configuration represented as a string
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
	protected function _updateInstanceCountInConfiguration($roleName, $instanceCount, $configuration) {
    	// Change variables
		if (!is_array($roleName)) {
			$roleName = array($roleName);
		}
		if (!is_array($instanceCount)) {
			$instanceCount = array($instanceCount);
		}

		$configuration = preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $configuration);
		//$configuration = '<?xml version="1.0">' . substr($configuration, strpos($configuration, '>') + 2);

		$xml = simplexml_load_string($configuration); 
		
		// http://www.php.net/manual/en/simplexmlelement.xpath.php#97818
		$namespaces = $xml->getDocNamespaces();
	    $xml->registerXPathNamespace('__empty_ns', $namespaces['']); 
	
		for ($i = 0; $i < count($roleName); $i++) {
			$elements = $xml->xpath('//__empty_ns:Role[@name="' . $roleName[$i] . '"]/__empty_ns:Instances');
	
			if (count($elements) == 1) {
				$element = $elements[0];
				$element['count'] = $instanceCount[$i];
			} 
		}
		
		$configuration = $xml->asXML();
		//$configuration = preg_replace('/(<\?xml[^?]+?)utf-8/i', '$1utf-16', $configuration);

		return $configuration;
	}
    
    /**
     * The Change Deployment Configuration request may be specified as follows.
     * Note that you can change a deployment's configuration either by specifying the deployment
     * environment (staging or production), or by specifying the deployment's unique name. 
     * 
     * @param string $serviceName		The service name
     * @param string $deploymentSlot	The deployment slot (production or staging)
     * @param string $configuration     XML configuration represented as a string
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function configureDeploymentBySlot($serviceName, $deploymentSlot, $configuration)
    {
        if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
    	$deploymentSlot = strtolower($deploymentSlot);
    	if ($deploymentSlot != 'production' && $deploymentSlot != 'staging') {
    		throw new Microsoft_WindowsAzure_Management_Exception('Deployment slot should be production|staging.');
    	}
    	if ($configuration == '' || is_null($configuration)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Configuration name should be specified.');
    	}
    	
    	$operationUrl = self::OP_HOSTED_SERVICES . '/' . $serviceName . '/deploymentslots/' . $deploymentSlot;
    	return $this->_configureDeployment($operationUrl, $configuration);
    }
    
    /**
     * The Change Deployment Configuration request may be specified as follows.
     * Note that you can change a deployment's configuration either by specifying the deployment
     * environment (staging or production), or by specifying the deployment's unique name. 
     * 
     * @param string $serviceName		The service name
     * @param string $deploymentName	The deployment name
     * @param string $configuration     XML configuration represented as a string
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    public function configureDeploymentByName($serviceName, $deploymentName, $configuration)
    {
        if ($serviceName == '' || is_null($serviceName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Service name should be specified.');
    	}
    	if ($deploymentName == '' || is_null($deploymentName)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Deployment name should be specified.');
    	}
    	if ($configuration == '' || is_null($configuration)) {
    		throw new Microsoft_WindowsAzure_Management_Exception('Configuration name should be specified.');
    	}
    	
    	$operationUrl = self::OP_HOSTED_SERVICES . '/' . $serviceName . '/deployments/' . $deploymentName;
    	return $this->_configureDeployment($operationUrl, $configuration);
    }
    
    /**
     * The Change Deployment Configuration request may be specified as follows.
     * Note that you can change a deployment's configuration either by specifying the deployment
     * environment (staging or production), or by specifying the deployment's unique name. 
     * 
     * @param string $operationUrl		The operation url
     * @param string $configuration     XML configuration represented as a string
     * @throws Microsoft_WindowsAzure_Management_Exception
     */
    protected function _configureDeployment($operationUrl, $configuration)
    {
    	// Clean up the configuration
    	$conformingConfiguration = $configuration;
		$conformingConfiguration = str_replace("\r", "", $conformingConfiguration);
		$conformingConfiguration = str_replace("\n", "", $conformingConfiguration);

        $response = $this->_performRequest($operationUrl . '/', '?comp=config',
    		Microsoft_Http_Client::POST,
    		array('Content-Type' => 'application/xml; charset=utf-8'),
    		'<ChangeConfiguration xmlns="http://schemas.microsoft.com/windowsazure" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><Configuration>' . base64_encode($conformingConfiguration) . '</Configuration></ChangeConfiguration>');
			 
    	if (!$response->isSuccessful()) {
			throw new Microsoft_WindowsAzure_Management_Exception($this->_getErrorMessage($response, 'Resource could not be accessed.'));
		}
    }
}
