<?php
namespace app\components\llm;

interface LlmProviderInterface
{
	/**
	 * @param string $user    пользовательский prompt
	 * @param string $system  system prompt / preset
	 * @return string|null
	 */
	public function prompt(string $user, string $system): ?string;
	
	public static function available(): bool;
}