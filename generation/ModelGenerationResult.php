<?php

namespace app\generation;

use app\generation\exceptions\ModelGenerationException;
use app\models\base\ArmsModel;

class ModelGenerationResult
{
	public function __construct(
		public ?ArmsModel $model,
		public ?ModelGenerationException $error,
	) {}
	
	public function isSuccess(): bool
	{
		return $this->model !== null;
	}
}