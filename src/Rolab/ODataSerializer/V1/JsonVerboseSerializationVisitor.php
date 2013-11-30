<?php

namespace Rolab\ODataSerializer\V1;

use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;


class JsonVerboseSerializationVisitor extends AbstractVisitor
{
	protected $navigator;
	
	protected $root;
	
	protected $dataStack;
	
	protected $data;
	
	protected $entityIdStack;
	
	protected $currentProperty;
	
	public function setNavigator(GraphNavigator $navigator)
	{
		$this->root = null;
		$this->navigator = $navigator;
		$this->dataStack = new \SplStack;
		$this->data = null;
		$this->entityIdStack = new \SplStack;
	}
	
	public function getNavigator()
	{
		return $this->navigator;
	}
		
	public function visitNull($data, array $type, Context $context)
	{		
		if (is_null($this->root)) {
			$this->setPrimitiveAsRoot($data, $context);
		}
		
		return null;
	}
	
	public function visitString($data, array $type, Context $context)
	{
		if (is_null($this->root)) {
			$this->setPrimitiveAsRoot($data, $context);
		}
		
		return (string) $data;
	}
	
	public function visitBoolean($data, array $type, Context $context)
	{
		if (is_null($this->root)) {
			$this->setPrimitiveAsRoot($data, $context);
		}
		
		return (boolean) $data;
	}
	
	public function visitDouble($data, array $type, Context $context)
	{
		if (is_null($this->root)) {
			$this->setPrimitiveAsRoot($data, $context);
		}
		
		return (float) $data;
	}
	
	public function visitInteger($data, array $type, Context $context)
	{
		if (is_null($this->root)) {
			$this->setPrimitiveAsRoot($data, $context);
		}
		
		return (int) $data;
	}
	
	public function visitArray($data, array $type, Context $context)
	{
		if (is_null($this->root)) {
			$this->root = array();
			$isTopLevel = true;
        }
		
		$results = array();
		
		foreach ($data as $key => $value) {
			$value = $this->navigator->accept($value, isset($type['params'][1]) ? $type['params'][1] : null, $context);
			
			if (is_null($value) && !$context->shouldSerializeNull()) {
                continue;
            }

            $results[$key] = $value;
		}
		
		if ($isTopLevel) {
			$this->root['d'] = $results;
		}
		
		return $results;
	}
	
	public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
	{
		if (null === $this->root) {
            $this->root = new \stdClass;
        }

        $this->dataStack->push($this->data);
        $this->data = array();
		
		if ($context instanceof ODataSerializationContext) {
			$type = isset($this->currentProperty) ? $this->currentProperty->getResourceType() : $context->getResourceType();
			
			if ($type instanceof EntityType) {
				$entitySet = isset($this->currentProperty) ? $this->currentProperty->getTargetEntitySet() :
					$context->isProperty() ? $context->getProperty()->getTargetEntitySet() : $context->getEntitySet();
					
				$entityId = ODataSerializationHelper::getEntityId($data, $entitySet);
				
				$this->entityIdStack->push($entityId);
				
				$this->data['__metadata'] = array(
					'uri' => $entityId,
					'type' => $type->getFullName()
				);
				
				if ($type->hasETag()) {
					$this->data['__metadata']['etag'] = ODataSerializationHelper::getEntityETag($data, $type);
				}
			} else {
				$this->data['__metadata'] = array(
					'type' => $context->getProperty()->getResourceType()->getFullName()
				);
			}
		}
	}
	
	public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
	{
		$value = $metadata->getValue($data);
		
		$serializedName = $this->namingStrategy->translateName($metadata);
		
		if ($context instanceof ODataSerializationContext && $property = $context->getResourceType()->getPropertyByName($metadata->name)) {
			$this->currentProperty = $property;
			
			if ($property instanceof NavigationProperty
				&& !in_array(strtolower($metadata->name), array_map('strtolower', $context->getExpandedProperties()))
			) {
				$value = array(
					'__defered' => array(
						'uri' => $property instanceof EntitySetReferenceProperty ? 
							$this->entityIdStack->top() .'/'. $property->getName() :
							ODataSerializationHelper::getEntityId($value, $property->getTargetEntitySet())
					)
				);
			} else {					
				$value = $this->navigator->accept($value, $metadata->type, $context);
			}
			
			$this->currentProperty = null;
		} else {
			$value = $this->navigator->accept($value, $metadata->type, $context);
		}
		
        if (null === $value && !$context->shouldSerializeNull()) {
            return;
        }

        if ($metadata->inline && is_array($value)) {
            $this->data = array_merge($this->data, $value);
        } else {
            $this->data[$serializedName] = $value;
        }
	}
	
	public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
	{
		$result = $this->data;
        $this->data = $this->dataStack->pop();
		
		if ($context instanceof ODataSerializationContext) {
			$type = isset($this->currentProperty) ? $this->currentProperty->getResourceType() : $context->getResourceType();
			
			if ($type instanceof EntityType) {
				$this->entityIdStack->pop();
			}
		}

        if ($this->root instanceof \stdClass && 0 === $this->dataStack->count()) {
            $this->root = array('d' => $result);
        }

        return $result;
	}
	
	public function getResult()
    {
        return json_encode($this->getRoot());
    }
	
	protected function setPrimitiveAsRoot($data, Context $context)
	{
		if ($context instanceof ODataSerializationContext && $context->isProperty()) {
			$this->root = array(
				'd' => array(
					$context->getProperty()->getName() => $data
				)
			);
		} else {
			$this->root = $data;
		}
	}
}
