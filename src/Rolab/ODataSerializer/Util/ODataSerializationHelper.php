<?php

namespace Rolab\ODataSerializer\Util;

class ODataSerializationHelper
{
	public static function getEntityId($instance, EntitySet $entitySet)
	{
		
	}
	
	public static function getEntityETag($instance, EntitySet $entitySet)
	{
		
	}
	
	public static function getFeedId(ODataSerializationContext $context)
	{
		return $context->isProperty() ? $context->getEntitySet()->getFullName() .'/'. $context->getProperty()->getName()
			: $context->getEntitySet()->getFullName();
	}
}
