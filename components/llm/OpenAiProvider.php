<?php
namespace app\components\llm;

use OpenAI;

class OpenAiProvider implements LlmProviderInterface
{
	private OpenAI\Client $client;
	
	public static function available(): bool
	{
		return strlen(\Yii::$app->params['llm.openai.key'] ?? '') > 0;
	}
	
	public function __construct()
	{
		if (!static::available()) {
			throw new \RuntimeException('OpenAI not available');
		}
		
		$key   = \Yii::$app->params['llm.openai.key'];
		$proxy = \Yii::$app->params['llm.openai.proxy'] ?? null;
		
		if ($proxy) {
			$this->client = OpenAI::factory()
				->withApiKey($key)
				->withHttpClient(new \GuzzleHttp\Client([
					'proxy' => [
						'http'  => $proxy,
						'https' => $proxy,
					],
					'timeout' => 30,
				]))
				->make();
		} else {
			$this->client = OpenAI::client($key);
		}
	}
	
	public function prompt(string $user, string $system): ?string
	{
		$response = $this->client->chat()->create([
			'model' => 'gpt-4o-mini',
			'messages' => [
				['role' => 'system', 'content' => $system],
				['role' => 'user', 'content' => $user],
			],
		]);
		
		return $response->choices[0]->message->content ?? null;
	}
}