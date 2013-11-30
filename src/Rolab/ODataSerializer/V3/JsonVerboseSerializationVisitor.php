<?php

namespace Rolab\ODataSerializer\V3;

use Rolab\ODataSerializer\V2\JsonVerboseSerializationVisitor as V2JsonVerboseSerializationVisitor;

class JsonVerboseSerializationVisitor extends V2JsonVerboseSerializationVisitor
{
	public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
	{
		parent::startVisitingObject($metadata, $data, $type, $context);
		
		if ($context instanceof ODataSerializationContext) {
			if ($context->getResourceType() instanceof EntityType) {
				foreach ($type->getProperties() as $property) {
					if ($property instanceof NavigationProperty) {
						$this->data['__metadata'][$property->getName()] =  array(
							'associationuri' => $this->entityIdStack->top() . '/$links/'. $property->getName()
						);
					}
				}
			}
		}
	}
}
