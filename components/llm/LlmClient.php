<?php

namespace app\components\llm;

use OpenAI;
class LlmClient
{
	private $client;
	
	/**
	 * Возвращает true, если LLM доступны для использования
	 * @return boolean
	 */
	public static function available()
	{
		return strlen(\Yii::$app->params['llm.openai.key']??'')>0;
	}
	
	public function __construct()
	{
		$apiKey = \Yii::$app->params['llm.openai.key'];
		$proxy = \Yii::$app->params['llm.openai.proxy']??'';
		if ($proxy) {
			
			$this->client = OpenAI::factory()
				->withApiKey($apiKey)
				->withHttpClient(new \GuzzleHttp\Client([
					'proxy' => [
						'http' => $proxy,
						'https' => $proxy,
					],
					'timeout' => 30,
				]))
				->make();
		} else {
			$this->client = OpenAI::client($apiKey);
			
		}
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
		
		$response = $this->client->chat()->create([
			'model' => 'gpt-4o-mini', // дешёвая и качественная модель
			'messages' => [
				['role' => 'system', 'content' => 'Ты помощник, описывающий программное обеспечение в нейтральном техническом стиле.'],
				['role' => 'user', 'content' => $prompt],
			],
			'response_format' => ['type' => 'json_object'],
		]);
		
		$json = $response->choices[0]->message->content ?? null;
		return $json ? json_decode($json, true) : null;
	}
	
	
	public function generateTechModelDescription(string $type, string $name, string $tpl): string
	{
		$prompt = <<<PROMPT
Заполни краткое техническое описание устройства типа "$type" (модель "$name") по шаблону ниже.

Шаблон:
$tpl

Ответ должен быть в виде текстового блока (plain text) строго по структуре шаблона, без пояснений.
PROMPT;
		
		
		
		$response = $this->client->chat()->create([
			'model' => 'gpt-4o-mini', // дешёвая и качественная модель
			'messages' => [
				['role' => 'system', 'content' => 'Ты — эксперт по инвентаризации IT-оборудования.'],
				['role' => 'user', 'content' => $prompt],
			],
		]);
		
		return trim($response->choices[0]->message->content);
	}
	
}