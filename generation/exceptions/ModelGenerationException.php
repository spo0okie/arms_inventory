<?php

namespace app\generation\exceptions;

class ModelGenerationException extends \Exception
{
	public function __construct(
		public string $modelClass,
		public string $stage,	//стадия сборки
		public array $errors = [],
		public ?int $seed=null,	//детерминизм
		public ?string $attribute = null,
		public ?string $relatedClass = null,
		public ?int $depth = null,
		public array $values = [],
		public ?\Throwable $previous = null,
	) {
		parent::__construct($this->buildMessage(), 0, $previous);
	}
	
	protected function buildMessage(): string
	{
		$message = sprintf(
			"Model build failed: %s (seed=%d)\n"
			."stage: %s\n"
			."attr: %s\n"
			."related: %s\n"
			."depth: %s\n"
			."errors: %s\n"
			."vaules: %s",
			$this->modelClass,
			$this->seed ?? -1,
			$this->stage,
			$this->attribute ?? '-',
			$this->relatedClass ?? '-',
			$this->depth ?? '-',
			json_encode($this->errors, JSON_UNESCAPED_UNICODE),
			json_encode($this->values, JSON_UNESCAPED_UNICODE)
		)."\n".$this->getTraceAsString();
		
		if ($this->previous) {
			$message .= "\nprevious: " . $this->previous->getMessage().PHP_EOL.$this->previous->getTraceAsString();
		}
		
		return $message;
	}
}
