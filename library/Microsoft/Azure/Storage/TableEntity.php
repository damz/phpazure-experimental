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
 * @version    $Id: BlobInstance.php 14561 2009-05-07 08:05:12Z unknown $
 */

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
abstract class Microsoft_Azure_Storage_TableEntity
{
    /**
     * Partition key
     * 
     * @var string
     */
    protected $_partitionKey;
    
    /**
     * Row key
     * 
     * @var string
     */
    protected $_rowKey;
    
    /**
     * Timestamp
     * 
     * @var string
     */
    protected $_timestamp = '0001-01-01T00:00:00';
    
    /**
     * Constructor
     * 
     * @param string  $partitionKey    Partition key
     * @param string  $rowKey          Row key
     */
    public function __construct($partitionKey = '', $rowKey = '') 
    {	        
        $this->_partitionKey = $partitionKey;
        $this->_rowKey       = $rowKey;
    }
    
    /**
     * Get partition key
     * 
     * @azure PartitionKey
     * @return string
     */
    public function getPartitionKey()
    {
        return $this->_partitionKey;
    }
    
    /**
     * Set partition key
     * 
     * @azure PartitionKey
     * @param string $value
     */
    public function setPartitionKey($value)
    {
        $this->_partitionKey = $value;
    }
    
    /**
     * Get row key
     * 
     * @azure RowKey
     * @return string
     */
    public function getRowKey()
    {
        return $this->_rowKey;
    }
    
    /**
     * Set row key
     * 
     * @azure RowKey
     * @param string $value
     */
    public function setRowKey($value)
    {
        $this->_rowKey = $value;
    }
    
    /**
     * Get timestamp
     * 
     * @azure Timestamp Edm.DateTime
     * @return string
     */
    public function getTimestamp()
    {
        return $this->_timestamp;
    }
    
    /**
     * Set timestamp
     * 
     * @azure Timestamp Edm.DateTime
     * @param string $value
     */
    public function setTimestamp($value = '0001-01-01T00:00:00')
    {
        $this->_timestamp = $value;
    }
    
    /**
     * Get Azure values
     * 
     * @return array
     */
    public function getAzureValues()
    {
        // Get accessors
        $accessors = $this->getAzureAccessors();
        
        // Loop accessors and retrieve values
        $returnValue = array();
        foreach ($accessors as $accessor)
        {
            if ($accessor->EntityType == 'ReflectionProperty')
            {
                $property = $accessor->EntityAccessor;
                $returnValue[] = (object)array(
                    'Name'  => $accessor->AzurePropertyName,
                	'Type'  => $accessor->AzurePropertyType,
                	'Value' => $this->$property,
                );
            }
            else if ($accessor->EntityType == 'ReflectionMethod' && substr(strtolower($accessor->EntityAccessor), 0, 3) == 'get')
            {
                $method = $accessor->EntityAccessor;
                $returnValue[] = (object)array(
                    'Name'  => $accessor->AzurePropertyName,
                	'Type'  => $accessor->AzurePropertyType,
                	'Value' => $this->$method(),
                );
            }
        }
        
        // Return
        return $returnValue;
    }
    
    /**
     * Set Azure values
     * 
     * @param array $values
     * @param boolean $throwOnError Throw Microsoft_Azure_Exception when a property is not specified in $values?
     * @throws Microsoft_Azure_Exception
     */
    public function setAzureValues($values = array(), $throwOnError = false)
    {
        // Get accessors
        $accessors = $this->getAzureAccessors();
        
        // Loop accessors and set values
        $returnValue = array();
        foreach ($accessors as $accessor)
        {
            if (isset($values[$accessor->AzurePropertyName])) {
                if ($accessor->EntityType == 'ReflectionProperty')
                {
                    $property = $accessor->EntityAccessor;
                    $this->$property = $values[$accessor->AzurePropertyName];
                }
                else if ($accessor->EntityType == 'ReflectionMethod' && substr(strtolower($accessor->EntityAccessor), 0, 3) == 'set')
                {
                    $method = $accessor->EntityAccessor;
                    $this->$method($values[$accessor->AzurePropertyName]);
                }
            }
            else if ($throwOnError) 
            {
                throw new Microsoft_Azure_Exception("Property '" . $accessor->AzurePropertyName . "' was not found in \$values array");    
            }
        }
        
        // Return
        return $returnValue;
    }
    
    /**
     * Get Azure accessors from current instance
     * 
     * @return array
     */
    protected function getAzureAccessors()
    {
        // List of accessors
        $azureAccessors = array();
        
        // Get all types
        $type = new ReflectionObject($this);
        
        // Loop all properties
        $properties = $type->getProperties();
        foreach ($properties as $property)
        {
            $accessor = $this->getAzureAccessor($property);
            if (!is_null($accessor)) {
                $azureAccessors[] = $accessor;
            }
        }
        
        // Loop all methods
        $methods = $type->getMethods();
        foreach ($methods as $method)
        {
            $accessor = $this->getAzureAccessor($method);
            if (!is_null($accessor)) {
                $azureAccessors[] = $accessor;
            }
        }
        
        // Return
        return $azureAccessors;
    }
    
    /**
     * Get Azure accessor from reflection member
     * 
     * @param ReflectionProperty|ReflectionMethod $member
     * @return object
     */
    protected function getAzureAccessor($member)
    {
        // Get comment
        $docComment = $member->getDocComment();
        
        // Check for Azure comment
        if (strpos($docComment, '@azure') === false)
            return null;
            
        // Search for @azure contents
        $azureComment = '';
        $commentLines = explode("\n", $docComment);
        foreach ($commentLines as $commentLine)
        {
            if (strpos($commentLine, '@azure') !== false)
            {
                $azureComment = trim(substr($commentLine, strpos($commentLine, '@azure') + 6));
                while (strpos($azureComment, '  ') !== false)
                {
                    $azureComment = str_replace('  ', ' ', $azureComment);
                }
                break;
            }
        }
        
        // Fetch @azure properties
        $azureProperties = explode(' ', $azureComment);
        return (object)array(
            'EntityAccessor'    => $member->getName(),
            'EntityType'        => get_class($member),
            'AzurePropertyName' => $azureProperties[0],
        	'AzurePropertyType' => isset($azureProperties[1]) ? $azureProperties[1] : ''
        );
    }
}
