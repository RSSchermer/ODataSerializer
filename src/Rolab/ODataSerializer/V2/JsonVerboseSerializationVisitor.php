<?php

namespace Rolab\ODataSerializer\V2;

use JMS\Serializer\Context;

use Rolab\ODataSerializer\V1\JsonVerboseSerializationVisitor as V1JsonVerboseSerializationVisitor;

class JsonVerboseSerializationVisitor extends V1JsonVerboseSerializationVisitor
{
	public function visitArray($data, array $type, Context $context)
	{
		if (is_null($this->root)) {
			$isTopLevel = true;
		}
		
		$result = array();
		$result['results'] = parent::visitArray($data, $type, $context);
		
		if ($context instanceof ODataSerializationContext) {
			if (is_null($this->currentProperty) 
				&& (!$context->isProperty() || $context->getProperty() instanceof EntitySetReferenceProperty)) {
				if ($context->hasSkipToken()) {
					$result['__next'] = ($context->isProperty() ? 
						ODataSerializationHelper::getEntityId($data, $context->getEntitySet()) .'/'. $context->getProperty()->getName() :
						$context->getEntitySet()->getName()) . '?$skiptoken='. $context->getSkipToken();
				}
				
				if ($context->hasInlineCount()) {
					$result['__count'] = $context->getInlineCount();
				}
			}
		}
		
		if ($isTopLevel) {
			$this->root = array('d' => $result);
		}
		
		return $result;
	}
	
	public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
	{
		parent::startVisitingObject($metadata, $data, $type, $context);
		
		if ($context instanceof ODataSerializationContext) {
			$type = $context->getResourceType();
			
			if ($type instanceof EntityType) {
				$this->data['__metadata']['id'] = ODataSerializationHelper::getEntityKey($data, $type);
			}
		}
	}
	
	public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
	{
		if ($this->root instanceof \stdClass && 0 === $this->dataStack->count()) {
			$isTopLevel = true;
		}
		
		$result = parent::endVisititingObject($metadata, $data, $type, $context);
		
		if ($isTopLevel) {
			$this->root = array(
				'd' => array(
					'results' => $result
				)
			);
		}
		
		return $result;
	}
	
	protected function setPrimitiveAsRoot($data, Context $context)
	{
		if ($context instanceof ODataSerializationContext && $context->isProperty()) {
			$this->root = array(
				'd' => array(
					'results' => array(
						$context->getProperty()->getName() => $data
					)
				)
			);
		} else {
			$this->root = $data;
		}
	}
}
