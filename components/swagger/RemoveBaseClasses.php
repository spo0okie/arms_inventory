<?php

namespace app\components\swagger;

use OpenApi\Analysis;
use OpenApi\Generator;

class RemoveBaseClasses
{
	/** @var string[] */
	private array $baseClasses;
	
	public function __construct(array $baseClasses)
	{
		// список классов, которые нужно убрать
		$this->baseClasses = array_map(fn($c) => '\\'.ltrim($c, '\\'), $baseClasses);
	}
	
	public function __invoke(Analysis $analysis): void
	{
		foreach ($this->baseClasses as $baseClass) {
			unset($analysis->classes[$baseClass]);
		}
		
		$clear=[];
		foreach ($analysis->annotations as $ann) {
			$ctx = $ann->_context ?? null;
			if (!$ctx) {
				continue;
			}
			$fqn = $ctx->fullyQualifiedName($ctx->class ?? '');
			
			//$fqn = $ctx->fullyQualifiedName($ctx->class);
			if (in_array($fqn, $this->baseClasses, true)) {
				$clear[]=$ann;
				//unset($analysis->annotations[$k]);
			}
		}
		foreach ($clear as $ann) {
			$analysis->annotations->detach($ann);
		}
	}
}