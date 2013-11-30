<?php

namespace Rolab\ODataProducerBundle\Serializer\Exclusion;

use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Context;

use Rolab\ODataProducerBundle\Serializer\ODataSerializationContext;

class ODataExclusionStrategy implements ExclusionStrategyInterface
{
	public function shouldSkipClass(ClassMetadata $metadata, Context $context)
	{
		return false;
	}
	
	public function shouldSkipProperty(PropertyMetadata $property, Context $context)
	{
		if ($context instanceof ODataSerializationContext) {
			return $context->getResourceType()->getPropertyByName($property->name);
		}
		
		return false;
	}
}
