<?php

namespace app\components\llm;

use OpenAI;
class LlmClient
{
	private LlmProviderInterface $provider;
	
	/**
	 * Возвращает true, если LLM доступны для использования
	 * @return boolean
	 */
	public static function available(): bool
	{
		return OpenAiProvider::available()
			|| GigaChatProvider::available();
	}
	
	public function __construct()
	{
		if (OpenAiProvider::available()) {
			$this->provider = new OpenAiProvider();
		} elseif (GigaChatProvider::available()) {
			$this->provider = new GigaChatProvider();
		} else {
			throw new \RuntimeException('No LLM providers available');
		}
	}
	
	/**
	 * Убирает markdown-оформление блока с JSON (```json ... ```)
	 * @param string $text
	 * @return string
	 */
	public static function stripMarkdownJsonFence(string $text): string
	{
		$text = trim($text);
		/*
		^```            начало строки + ```
		(?:json)?       необязательное слово "json"
		\s*             перевод строки / пробелы
		(.*?)           сам JSON (ленивый захват)
		\s*				перевод строки / пробелы
		```$            закрывающие ```
		 */
		return preg_replace(
			'/^```(?:json)?\s*(.*?)\s*```$/si',
			'$1',
			$text
		) ?? $text;
	}
	
	/**
	 * Сгенерировать описание программного обеспечения в формате JSON
	 * @param string $name
	 * @return array|null
	 */
	public function generateSoftwareDescription(string $name): ?array
	{
		$prompt = "Создай описание программного обеспечения \"$name\" в формате JSON:
        {
          \"short\": \"Назначение (например, редактор изображений/просмотрщик PDF), - не более 100 знаков\",
          \"license\": \"Тип лицензии (например, freeware, open source, commercial, freemium и т.д.)\",
          \"cost\":\"платное/бесплатное/условно-бесплатное/бесплатное в комплекте с оборудованием\",
          \"description\": \"Описание: ключевые особенности, для чего используется, основные функции, известные проблемы, как могло оказаться на предприятии - не более 500 знаков\",
          \"links\": \"URL сайта программы\",
        }";
		
		$system='Ты помощник, описывающий программное обеспечение в нейтральном техническом стиле.';
		
		$response = $this->provider->prompt($prompt, $system);
		$response = static::stripMarkdownJsonFence($response);
		return $response ? json_decode($response, true) : null;
	}
	
	
	public function generateTechModelDescription(string $type, string $name, string $tpl): string
	{
		$prompt = <<<PROMPT
Заполни краткое техническое описание устройства типа "$type" (модель "$name") по шаблону ниже.

Шаблон:
$tpl

Ответ должен быть в виде текстового блока (plain text) строго по структуре шаблона, без пояснений.
PROMPT;
		$system='Ты — эксперт по инвентаризации IT-оборудования.';
		
		
		return $this->provider->prompt($prompt, $system);
	}
	
}