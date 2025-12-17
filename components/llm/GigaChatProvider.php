<?php
namespace app\components\llm;

use GuzzleHttp\Client;

class GigaChatProvider implements LlmProviderInterface
{
	private Client $apiHttp;
	private Client $authHttp;
	
	private string $authKey;      // Basic key
	private ?string $accessToken = null;
	
	public static function available(): bool
	{
		return strlen(\Yii::$app->params['llm.gigachat.token'] ?? '') > 0;
	}
	
	public function __construct()
	{
		if (!static::available()) {
			throw new \RuntimeException('GigaChat not available');
		}
		
		$this->authKey = \Yii::$app->params['llm.gigachat.token'];
		
		$common = [
			'timeout' => 30,
			'verify'  => false, // из-за нестандартных корней
			'curl' => [
				CURLOPT_SSLVERSION   => CURL_SSLVERSION_TLSv1_2,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			],
		];
		
		$this->authHttp = new Client($common + [
			'base_uri' => 'https://ngw.devices.sberbank.ru:9443/api/v2/',
		]);
		
		$this->apiHttp = new Client($common + [
			'base_uri' => 'https://gigachat.devices.sberbank.ru/api/v1/',
		]);
	}
	
	/**
	 * Получение OAuth access token
	 */
	private function getAccessToken(): string
	{
		if ($this->accessToken) {
			return $this->accessToken;
		}
		
		$response = $this->authHttp->post('oauth', [
			'headers' => [
				'Authorization' => 'Basic ' . $this->authKey,
				'Accept'        => 'application/json',
				'RqUID'         => $this->uuid(),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'form_params' => [
				'scope' => 'GIGACHAT_API_PERS',
			],
		]);
		
		$data = json_decode($response->getBody()->getContents(), true);
		
		if (empty($data['access_token'])) {
			throw new \RuntimeException('Failed to obtain GigaChat access token');
		}
		
		return $this->accessToken = $data['access_token'];
	}
	
	public function prompt(string $user, string $system): ?string
	{
		$token = $this->getAccessToken();
		$response = $this->apiHttp->post('chat/completions', [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'json' => [
				'model' => 'GigaChat',
				'messages' => [
					['role' => 'system', 'content' => $system],
					['role' => 'user', 'content' => $user],
				],
			],
		]);
		
		$data = json_decode($response->getBody()->getContents(), true);
		return $data['choices'][0]['message']['content'] ?? null;
	}
	
	private function uuid(): string
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			random_int(0, 0xffff),
			random_int(0, 0xffff),
			random_int(0, 0xffff),
			random_int(0, 0x0fff) | 0x4000,
			random_int(0, 0x3fff) | 0x8000,
			random_int(0, 0xffff),
			random_int(0, 0xffff),
			random_int(0, 0xffff)
		);
	}
}