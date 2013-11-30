<?php

namespace Rolab\ODataSerializer;

use JMS\Serializer\SerializationContext;

use Rolab\EntityDataModel\EntitySet;
use Rolab\EntityDataModel\Property\ResourceProperty;
use Rolab\EntityDataModel\Property\EntityReferenceProperty;
use Rolab\EntityDataModel\Property\EntitySetReferenceProperty;

class ODataSerializationContext extends SerializationContext
{	
	private $entityDataModel;
	
	private $serviceRootUri;
	
	private $entitySet;
	
	private $entityId;
	
	private $property;
	
	private $protocolVersion;
	
	private $expandedProperties = array();
	
	private $skipToken;
	
	private $inlineCount;
	
	public function __construct($serviceRootUri, 
								EntitySet $entitySet,
								$entityId = null,
								ResourceProperty $property = null,
								$protocolVersion = 1)
	{
		if (isset($property) && is_null($entityId)) {
			throw new \BadMethodCallException('A property may only be set when an entityId is also set.');
		}
		
		$this->serviceRootUri = $serviceRootUri;
		$this->entitySet = $entitySet;
		$this->entityId = $entityId;
		$this->property = $property;
		$this->setProtocolVersion($protocolVersion);
	}
	
	public function getServiceRootUri($serviceRootUri)
	{
		return $this->serviceRootUri;
	}
	
	public function getEntitySet()
	{
		return $this->entitySet;
	}
	
	public function getEntityId()
	{
		return $this->entityId;
	}
	
	public function getProperty()
	{
		return $this->property;
	}
	
	public function getResourceType()
	{
		return $this->isProperty() ? $this->property->getResourceType() : $this->entitySet->getEntityType();
	}
	
	public function isEntity()
	{
		return isset($this->entityId) && is_null($this->property) || $this->property instanceof EntityReferenceProperty;
	}
	
	public function isFeed()
	{
		return is_null($this->entityId) || $this->property instanceof EntitySetReferenceProperty;
	}
	
	public function isProperty()
	{
		return isset($this->property);
	}
	
	public function setProtocolVersion($protocolVersion)
	{
		$this->assertMutable();
		
		if (!is_int($protocolVersion) || $protocolVersion < 1 || $protocolVersion > 3) {
			throw new \InvalidArgumentException('ProtocolVersion must be an integer and must be greater than or ' .
				'equal to 1 and smaller than or equal to 3.');
		}
		
		$this->protocolVersion = $protocolVersion;
	}
	
	public function getProtocolVersion()
	{
		return $this->protocolVersion;
	}
	
	public function setExpandedProperties(array $expandedProperties)
	{
		$this->expandedProperties = $expandedProperties;
	}
	
	public function getExpandedProperties()
	{
		return $this->expandedProperties;
	}
	
	public function setSkipToken($skiptoken)
	{
		$this->skipToken = $skiptoken;
	}
	
	public function getSkipToken()
	{
		return $this->skipToken;
	}
	
	public function hasSkipToken()
	{
		return isset($this->skipToken);
	}
	
	public function setInlineCount($inlineCount)
	{
		$this->inlineCount = $inlineCount;
	}
	
	public function getInlineCount()
	{
		return $this->inlineCount;
	}
	
	public function hasInlineCount()
	{
		return isset($this->inlineCount);
	}
}
