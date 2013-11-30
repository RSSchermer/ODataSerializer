<?php

namespace Rolab\ODataSerializer;

use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

use PhpCollection\MapInterface;

use Rolab\ODataSerializer\ODataSerializationContext;
 
class VersionChainVisitor extends AbstractVisitor
{
	private $versionVisitors;
	
	private $navigator;
	
	private $activeVersion;
	
	private $activeVisitor;
	
	public function __construct(MapInterace $versionVisitors)
	{
		$this->versionVisitors = $versionVisitors;
	}
	
	public function setNavigator(GraphNavigator $navigator)
	{
		foreach ($this->versionVisitor as $versionVisitor) {
			$versionVisitor->setNavigator($navigator);
		}
		
		$this->navigator = $navigator;
		$this->activeVersion = null;
		$this->activeVisitor = null;
	}
	
	public function getNavigator()
	{
		return $this->navigator;
	}
		
	public function visitNull($data, array $type, Context $context)
	{
		return $this->getVersionVisitor($context)->visitNull($data, $type, $context);
	}
	
	public function visitString($data, array $type, Context $context)
	{
		return $this->getVersionVisitor($context)->visitString($data, $type, $context);
	}
	
	public function visitBoolean($data, array $type, Context $context)
	{
		return $this->getVersionVisitor($context)->visitBoolean($data, $type, $context);
	}
	
	public function visitDouble($data, array $type, Context $context)
	{
		return $this->getVersionVisitor($context)->visitDouble($data, $type, $context);
	}
	
	public function visitInteger($data, array $type, Context $context)
	{
		return $this->getVersionVisitor($context)->visitInteger($data, $type, $context);
	}
	
	public function visitArray($data, array $type, Context $context)
	{
		return $this->getVersionVisitor($context)->visitArray($data, $type, $context);
	}
	
	public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
	{
		$this->getVersionVisitor($context)->visitNull($metadata, $data, $type, $context);
	}
	
	public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
	{
		$this->getVersionVisitor($context)->visitNull($metadata, $data, $type, $context);
	}
	
	public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
	{
		return $this->getVersionVisitor($context)->visitNull($metadata, $data, $type, $context);
	}
	
	public function getResult()
    {
        return $this->activeVisitor->getResult();
    }
	
	private function getVersionVisitor(Context $context)
	{
		if ($context instanceof ODataSerializationContext) {
			$version = $context->getProtocolVersion();
		} else {
			$version = 1;
		}
		
		if (isset($this->activeVersion)) {
			if ($version !== $this->activeVersion) {
				throw new \RuntimeException('Version should not change during graph navigation. ' .
					'Reset the navigator before changing version.');
			}
			
			return $this->activeVisitor;
		}
		
		$this->activeVersion = $version;
		$this->activeVisitor = $this->versionVisitors->get($version)->get();
		
		return $this->activeVersion;
	}
}
